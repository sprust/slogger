import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {handleApiError} from "../utils/helpers.ts";

type TraceTimestampPeriod = AdminApi.TraceAggregatorTraceTimestampPeriodsList.ResponseBody['data'][number];

interface State {
    loaded: boolean,
    timestampPeriods: Array<TraceTimestampPeriod>
}

export const traceAggregatorTimestampPeriodStore = createStore<State>({
    state: {
        loaded: false,
        timestampPeriods: new Array<TraceTimestampPeriod>()
    } as State,
    mutations: {
        setPeriods(state: State, periods: Array<TraceTimestampPeriod>) {
            state.timestampPeriods = periods
        },
    },
    actions: {
        findTimestampPeriods({commit, state}: { commit: any, state: State }) {

            ApiContainer.get().traceAggregatorTraceTimestampPeriodsList()
                .then(response => {
                    commit('setPeriods', response.data.data)

                    state.loaded = true
                })
                .catch((error) => {
                    handleApiError(error)
                })
        }
    },
})

export const traceAggregatorTimestampPeriodStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorTimestampPeriodStore(): Store<State> {
    return baseUseStore(traceAggregatorTimestampPeriodStoreInjectionKey)
}
