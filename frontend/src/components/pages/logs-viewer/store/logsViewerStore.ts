import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {handleApiError} from "../../../../utils/helpers.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";

type LogsPagination = AdminApi.LogsList.ResponseBody['data']
type LogsParameters = AdminApi.LogsList.RequestQuery

type LogItem = AdminApi.LogsList.ResponseBody['data']['items'][number]

interface LogsViewerStoreInterface {
    loading: boolean,
    parameters: LogsParameters
    logs: LogsPagination
}

export const useLogsViewerStore = defineStore('logsViewerStore', {
    state: (): LogsViewerStoreInterface => {
        return {
            loading: true,
            parameters: {
                page: 1,
                search_query: null,
                level: null,
            },
            logs: {
                items: new Array<LogItem>(),
                paginator: {
                    total: 0,
                    per_page: 0,
                    current_page: 1,
                    total_pages: 0
                }
            }
        }
    },
    actions: {
        async findLogs() {
            this.loading = true

            const parameters: LogsParameters = {
                page: this.parameters.page
            }

            if (this.parameters.search_query) {
                parameters.search_query = this.parameters.search_query
            }

            if (this.parameters.level) {
                parameters.level = this.parameters.level
            }

            return handleApiRequest(
                ApiContainer.get()
                    .logsList(parameters)
                    .then((response) => {
                        this.logs = response.data.data
                    })
                    .catch((error) => {
                        handleApiError(error)
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
    },
})
