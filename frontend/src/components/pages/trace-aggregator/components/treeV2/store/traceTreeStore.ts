import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";
import {ApiContainer} from "../../../../../../utils/apiContainer.ts";

type TreeChildrenParameters = AdminApi.TraceAggregatorTracesTreeChildrenCreate.RequestBody
type TreeChildrenResponse = AdminApi.TraceAggregatorTracesTreeChildrenCreate.ResponseBody['data']
type TreeChild = AdminApi.TraceAggregatorTracesTreeChildrenCreate.ResponseBody['data']['items'][number]

interface TreeChildInterface {
    parametersForNext: TreeChildrenParameters,
    loading: boolean,
    item: TreeChild,
    children: TreeChildInterface[],
    hasMore: boolean,
}

interface TreeChildrenStoreInterface {
    loading: boolean,
    tree: TreeChildInterface[]
}

export const useTraceAggregatorTreeChildrenStore = defineStore('traceAggregatorTreeChildrenStore', {
    state: (): TreeChildrenStoreInterface => {
        return {
            loading: false,
            tree: new Array<TreeChildInterface>,
        }
    },
    actions: {
        async findRoot(traceId: string) {
            this.$reset()

            this.loading = true

            const parameters: TreeChildrenParameters = {
                page: 1,
                root: true,
                traceId: traceId,
            }

            return await handleApiRequest(
                ApiContainer.get().traceAggregatorTracesTreeChildrenCreate(parameters)
                    .then(response => {
                        const data: TreeChildrenResponse = response.data.data

                        data.items.forEach((item: TreeChild) => {
                            this.tree.push({
                                parametersForNext: {
                                    page: 1,
                                    root: false,
                                    traceId: item.trace_id,
                                },
                                loading: false,
                                item: item,
                                children: [],
                                hasMore: true,
                            })
                        })

                        return response
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
    },
})
