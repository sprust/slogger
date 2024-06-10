import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {handleApiError} from "../utils/helpers.ts";

export type TraceAggregatorDetail = AdminApi.TraceAggregatorTracesDetail.ResponseBody['data'];
export type TraceAggregatorDetailData = AdminApi.TraceAggregatorTracesDetail.ResponseBody['data']['data'];

type TraceDataItem = {
    loaded: boolean
    data: TraceAggregatorDetailData
}

interface TraceDataItems {
    [key: string]: TraceDataItem;
}

interface State {
    dataItems: TraceDataItems,
}

export const traceAggregatorDataStore = createStore<State>({
    state: {
        dataItems: {} as TraceDataItems,
    } as State,
    mutations: {
        setData(
            state: State,
            {traceId, trace}: {traceId: string, trace: TraceAggregatorDetail}
        ) {
            state.dataItems[traceId] = {
                loaded: true,
                data: trace.data
            }
        },
        clearTraceData(state: State) {
            state.dataItems = {}
        },
    },
    actions: {
        findTraceData({commit}: {commit: any}, traceId: string) {
            ApiContainer.get().traceAggregatorTracesDetail(traceId)
                .then(response => {
                    commit('setData', {
                        traceId: traceId,
                        trace: response.data
                    })
                })
                .catch((error) => {
                    handleApiError(error)
                })
        },
        clearTraceData({commit}: {commit: any}) {
            commit('clearTraceData')
        }
    },
})

export const traceAggregatorDataStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorDataStore(): Store<State> {
    return baseUseStore(traceAggregatorDataStoreInjectionKey)
}
