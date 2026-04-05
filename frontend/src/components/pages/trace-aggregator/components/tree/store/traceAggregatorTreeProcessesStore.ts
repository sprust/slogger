import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

export type TraceTreeProcess = AdminApi.TraceAggregatorTracesTreeProcessesList.ResponseBody['data'][number]

interface TraceAggregatorTreeProcessesStoreInterface {
    loading: boolean,
    processes: Array<TraceTreeProcess>,
}

export const useTraceAggregatorTreeProcessesStore = defineStore('traceAggregatorTreeProcessesStore', {
    state: (): TraceAggregatorTreeProcessesStoreInterface => {
        return {
            loading: false,
            processes: [],
        }
    },
    actions: {
        async findProcesses() {
            this.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().traceAggregatorTracesTreeProcessesList()
                    .then(response => {
                        this.processes = response.data.data

                        return response
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        async cancelProcess(rootTraceId: string) {
            return await handleApiRequest(
                () => ApiContainer.get().traceAggregatorTracesTreeProcessesCancelPartialUpdate({
                    root_trace_id: rootTraceId,
                }).then(response => {
                    this.processes = this.processes.map(
                        (process: TraceTreeProcess) => process.root_trace_id === rootTraceId
                            ? response.data.data
                            : process
                    )

                    return response
                })
            )
        },
        async deleteProcess(rootTraceId: string) {
            return await handleApiRequest(
                () => ApiContainer.get().traceAggregatorTracesTreeProcessesDeletePartialUpdate({
                    root_trace_id: rootTraceId,
                }).then(response => {
                    this.processes = this.processes.filter(
                        (process: TraceTreeProcess) => process.root_trace_id !== rootTraceId
                    )

                    return response
                })
            )
        },
    },
})
