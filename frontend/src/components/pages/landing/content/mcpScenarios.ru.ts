import type {McpScenario} from "./types.ts";

export const mcpScenariosRu: Array<McpScenario> = [
    {
        name: 'load',
        title: 'Кто нагружает',
        question: 'Кто-то даёт нагрузку на billing на prod. Посмотри, кто.',
        steps: [
            {
                tool: 'get_services',
                params: 'query: "billing"',
                result: 'billing — сервис 2',
            },
            {
                tool: 'aggregate_traces',
                params: 'service_ids: [2], from: 13:00, to: 16:00, by: ["minute10"]',
                result: 'с 14:20 до 14:40 трейсов в 6 раз больше обычного',
            },
            {
                tool: 'aggregate_traces',
                params: 'service_ids: [2], from: 14:20, to: 14:40, by: ["type"]',
                result: 'рост дают запросы request, остальные типы на прежнем уровне',
            },
            {
                tool: 'get_trace_tree',
                params: 'trace_id: <трейс billing из окна>',
                result: 'запросы billing вызывает gateway POST /api/invoices',
            },
            {
                tool: 'search_traces',
                params: 'service_ids: [1], from: 14:20, to: 14:40, tags: ["POST /api/invoices"], data_fields: ["request.ip", "user.id"]',
                result: 'в выборке почти все запросы с адреса 10.0.4.17 от user.id 812',
            },
        ],
        answer: 'Всплеск 14:20–14:40 в billing создают вызовы из gateway POST /api/invoices. В выборке трейсов gateway за это окно почти все запросы пришли с адреса 10.0.4.17 от пользователя 812. Остальные типы трейсов billing не выросли.',
    },
    {
        name: 'errors',
        title: 'Почему падают',
        question: 'Почему у billing иногда падают API-запросы?',
        steps: [
            {
                tool: 'aggregate_traces',
                params: 'service_ids: [2], from, to: последние сутки, statuses: ["failed"], by: ["hour"]',
                result: 'ошибки идут волнами по 10–15 минут',
            },
            {
                tool: 'compare_trace_groups',
                params: 'group_a_statuses: ["failed"], by: "data.response.status"',
                result: 'у упавших почти всегда 504, у остальных 200',
            },
            {
                tool: 'compare_trace_groups',
                params: 'group_a_statuses: ["failed"], by: "tag"',
                result: 'упавшие чаще всего с тегом POST /internal/invoices',
            },
            {
                tool: 'search_traces',
                params: 'statuses: ["failed"], data_filter: ["response.status = 504"]',
                result: 'примеры упавших трейсов',
            },
            {
                tool: 'search_trace_tree',
                params: 'trace_id: <упавший трейс>, statuses: ["failed"]',
                result: 'внутри падает вызов crm с таймаутом',
            },
        ],
        answer: 'Падают POST /internal/invoices с ответом 504: внутри них не дожидается ответа вызов crm. Ошибки идут волнами, совпадающими по времени с ростом нагрузки на crm. Для повторения есть промпт investigate_errors.',
    },
    {
        name: 'latency',
        title: 'Что замедлилось',
        question: 'Что тормозит в billing со вчерашнего вечера?',
        steps: [
            {
                tool: 'aggregate_traces',
                params: 'service_ids: [2], from, to: с вечера, by: ["hour"]',
                result: 'p95 длительности вырос с 0.3 до 1.8 секунды после 19:00',
            },
            {
                tool: 'aggregate_traces',
                params: 'from: 19:00, to: сейчас, by: ["type"]',
                result: 'вырос p95 у request, у database и queue без изменений',
            },
            {
                tool: 'search_traces',
                params: 'types: ["request"], duration_from: 1.5',
                result: 'медленные запросы — отчёт GET /api/reports',
            },
            {
                tool: 'get_trace_tree',
                params: 'trace_id: <медленный трейс>',
                result: 'внутри сотни одинаковых запросов в базу',
            },
        ],
        answer: 'С 19:00 медленнее стал GET /api/reports: на каждую строку отчёта выполняется отдельный запрос в базу. Остальные типы трейсов не замедлились. Для повторения есть промпт investigate_latency.',
    },
    {
        name: 'incident',
        title: 'Что за инцидент',
        question: 'Что за инцидент висит на prod?',
        steps: [
            {
                tool: 'get_incidents',
                params: 'status: "opened"',
                result: 'открыт инцидент смотрителя slowTraces по billing',
            },
            {
                tool: 'get_incident_events',
                params: 'incident_id: <id>',
                result: 'порог 2 секунды, самый долгий трейс 4.7 секунды, группы по типам',
            },
            {
                tool: 'aggregate_traces',
                params: 'from, to: окно инцидента, by: ["minute10"]',
                result: 'долгие трейсы сосредоточены в 14:20–14:40',
            },
            {
                tool: 'search_traces',
                params: 'from, to: окно инцидента, duration_from: 2',
                result: 'долгие трейсы — POST /internal/invoices',
            },
        ],
        answer: 'Инцидент завёл смотритель долгих трейсов billing: с 14:20 до 14:40 POST /internal/invoices выполнялись дольше 2 секунд, самый долгий — 4.7 секунды. Для повторения есть промпт explain_incident.',
    },
    {
        name: 'trace',
        title: 'Разбор трейса',
        question: 'Разбери трейс gateway-3b91d0e5.',
        steps: [
            {
                tool: 'get_trace',
                params: 'trace_id: "gateway-3b91d0e5"',
                result: 'POST /api/invoices, failed, 4.7 секунды',
            },
            {
                tool: 'get_trace_tree',
                params: 'trace_id: "gateway-3b91d0e5"',
                result: 'gateway → billing → crm, 14 узлов',
            },
            {
                tool: 'search_trace_tree',
                params: 'trace_id: "gateway-3b91d0e5", statuses: ["failed"]',
                result: 'упал вызов crm',
            },
            {
                tool: 'get_trace_data',
                params: 'trace_id: <упавший узел>',
                result: 'в данных ответ 504 и время ожидания',
            },
        ],
        answer: 'Запрос упал, потому что billing не дождался ответа crm и вернул 504. Остальные вызовы внутри дерева прошли успешно. Для повторения есть промпт explain_trace.',
    },
    {
        name: 'release',
        title: 'После релиза',
        question: 'Что изменилось после релиза в 14:00?',
        steps: [
            {
                tool: 'aggregate_traces',
                params: 'from: 12:00, to: 16:00, by: ["hour", "type"]',
                result: 'количество, ошибки и p95 по типам до и после 14:00',
            },
            {
                tool: 'aggregate_traces',
                params: 'from: 14:00, to: 16:00, statuses: ["failed"], by: ["type"]',
                result: 'после релиза появились ошибки у queue',
            },
            {
                tool: 'compare_trace_groups',
                params: 'from: 14:00, to: 16:00, group_a_statuses: ["failed"], by: "tag"',
                result: 'падает одна задача очереди',
            },
        ],
        answer: 'После 14:00 начала падать одна задача очереди, остальные типы трейсов без изменений по количеству и p95.',
    },
    {
        name: 'data',
        title: 'Поиск по данным',
        question: 'Найди упавшие счета с суммой больше 10 000.',
        steps: [
            {
                tool: 'get_trace_data_fields',
                params: 'type: "request"',
                result: 'есть ключи request.uri, invoice.amount, response.status',
            },
            {
                tool: 'search_traces',
                params: 'statuses: ["failed"], data_filter: ["invoice.amount > 10000"], data_fields: ["invoice.amount"]',
                result: 'список трейсов с суммами',
            },
        ],
        answer: 'Нашлось 12 упавших запросов с суммой больше 10 000. Суммы и идентификаторы трейсов — в списке.',
    },
    {
        name: 'missing',
        title: 'Нет трейсов',
        question: 'Почему трейсы не видны в панели?',
        steps: [
            {
                tool: 'get_trace_time_range',
                params: '',
                result: 'последний час с трейсами — два часа назад',
            },
            {
                tool: 'get_incidents',
                params: 'status: "opened"',
                result: 'открыт инцидент смотрителя bufferOverflow',
            },
            {
                tool: 'search_slogger_logs',
                params: 'source: "Receiver", levels: ["error"]',
                result: 'приёмник не может записать в ClickHouse',
            },
        ],
        answer: 'Трейсы принимаются, но не записываются: приёмник не может писать в ClickHouse, и буфер растёт. Об этом же говорит открытый инцидент смотрителя буфера.',
    },
    {
        name: 'installations',
        title: 'Две установки',
        question: 'Сравни ошибки billing на stand и prod.',
        steps: [
            {
                tool: 'slogger-stand: aggregate_traces',
                params: 'statuses: ["failed"], by: ["type"]',
                result: 'ошибки stand по типам',
            },
            {
                tool: 'slogger-prod: aggregate_traces',
                params: 'statuses: ["failed"], by: ["type"]',
                result: 'ошибки prod по типам',
            },
        ],
        answer: 'Каждая установка подключена отдельным сервером, данные у них не общие, поэтому модель спрашивает обе и сравнивает ответы. Если установка не названа, а подключено несколько, модель уточняет, какую смотреть.',
    },
]
