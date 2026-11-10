# Тесты SLogger

Читать перед добавлением или изменением тестов вместе с [правилами](../rules.md).

- Интеграционные тесты наследуют `Tests\Modules\BaseModuleTestCase`; зависимости разрешаются через
  `$this->appMake(...)`.
- Новые бизнес-тесты проверяют публичный API сценария и состояние хранилища.
  Сидинг — фабриками своей сущности и фабриками связанных сущностей. Фабрика
  readonly, принимает собственный repository и dependent factories; `create`
  полностью опционален (`?type = null`), возвращает id, использует `??` для fallback.
  Для enum fallback — случайный case; нужное сценарию значение задаётся явно.
- Для вариаций одного сценария использовать один `test` с `#[DataProvider]` и
  понятными именами наборов. DTO для чистого преобразования собирать в памяти.
- Новые или переписываемые бизнес-тесты не подменяют Actions/Repositories PHPUnit
  mocks и не исследуют private-методы Reflection. Зависимости — через контейнер,
  явные stateless stubs допустимы для внешних контрактов.
- Анализаторы исходного кода проверяются настоящим CLI на изолированных fixtures;
  чистые Transports — на типизированных входных данных; транспортные сбои и
  конкурентность dispatcher — контролируемыми реализациями его внешних контрактов.
  Эти проверки не требуют базы или Laravel-приложения.
- `CreatesApplication` устанавливает `Event::fake()` до boot providers, чтобы
  singleton DispatcherInterface получил fake. `TestCase` вызывает
  `Http::preventStrayRequests()` после bootstrap.
  Тест настоящих listeners явно задаёт `protected bool $fakeEvents = false`.
  Для внешнего HTTP API задавать конкретный fake/stub.
- `make test-prepare` поднимает MySQL, MongoDB, ClickHouse и Redis профиля `testing`
  и пересоздаёт только тестовые схемы. Данные баз находятся на tmpfs; Redis не
  сохраняется. После `make test-down` или перезапуска нужна повторная подготовка.
  `make test-up` только запускает сервисы, `make test-art c="..."` выполняет Artisan
  в той же среде. Рабочие сервисы для тестов не нужны.
- `.env.testing` — статический отслеживаемый файл с публичными тестовыми значениями.
  Не генерировать его из `.env`. `tests/bootstrap.php` загружает его до Laravel;
  guard проверяет адреса/имена/URL до providers. PHPUnit и tests/console.php
  используют общий bootstrap и отдельные cache paths.
- Не использовать `RefreshDatabase` / `DatabaseTransactions`: каждый кейс создаёт
  свежие parent-сущности. Не рассчитывать на пустую БД; suite должен проходить
  повторно без reset. Отсутствие таблиц исправлять через `make test-prepare`.
- Фабрики находятся в `tests/Factories/Entities`. Для SQL возвращать int id,
  для Mongo/trace — string id; UserTokenFactory возвращает сам токен. TraceFactory
  использует ClickhouseClient: production insert принадлежит Go, у PHP repository
  нет метода записи. Остальные фабрики используют свой repository и dependent factories.
  Чистые DTO fixtures не заменять DB-сидингом.
- Assert-вызовы вертикально, без trailing comma. `assertSame` для скаляров и типов,
  `assertEquals` для сравнения вложенных DTO; порядок и null проверять явно.
  Полезное сообщение указывает набор/поле. Static callbacks не захватывают `$this`.
- Сохранять статусы, поля и тексты HTTP-ответов. Не менять ожидаемый контракт
  лишь для того, чтобы тест прошёл после рефакторинга.
- Ошибки dispatcher тестировать, а не избегать: отказ регистрации/маршрута должен
  писать в лог напрямую, без повторного report и без отправки диагностической job.
- Команда: `make test c="--filter=ClassName"`; после backend — `make check`.
  Порядок методов после formatter проверять; dataProvider выше private helpers.
