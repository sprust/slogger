import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {handleApiError} from "../../../../utils/helpers.ts";

export type TraceTimestampPeriod = AdminApi.TraceAggregatorTraceTimestampPeriodsList.ResponseBody['data'][number];
export type TraceTimestampStep = AdminApi.TraceAggregatorTraceTimestampPeriodsList.ResponseBody['data'][number]['timestamps'][number];

interface State {
    loaded: boolean,

    timestampPeriods: Array<TraceTimestampPeriod>
    timestampSteps: Array<TraceTimestampStep>

    selectedTimestampPeriod: string,
    selectedTimestampStep: string,
}

export const traceAggregatorTimestampPeriodStore = createStore<State>({
    state: {
        loaded: false,
        timestampPeriods: new Array<TraceTimestampPeriod>(),
        timestampSteps: new Array<TraceTimestampStep>(),
        selectedTimestampPeriod: '',
        selectedTimestampStep: '',
    } as State,
    mutations: {
        setPeriods(state: State, periods: Array<TraceTimestampPeriod>) {
            state.timestampPeriods = periods

            state.selectedTimestampPeriod = state.timestampPeriods[0].period.value
        },
        freshTimestampSteps(state: State) {
            const selectedTimestampPeriod = state.timestampPeriods.find((periodItem: TraceTimestampPeriod) => {
                return periodItem.period.value === state.selectedTimestampPeriod
            })

            state.timestampSteps = selectedTimestampPeriod ? selectedTimestampPeriod.timestamps : []

            state.selectedTimestampStep = state.timestampSteps[0].value
        },
    },
    actions: {
        findTimestampPeriods({commit, state}: { commit: any, state: State }) {
            ApiContainer.get().traceAggregatorTraceTimestampPeriodsList()
                .then(response => {
                    commit('setPeriods', response.data.data)
                    commit('freshTimestampSteps')

                    state.loaded = true
                })
                .catch((error) => {
                    handleApiError(error)
                })
        },
        freshTimestampSteps({commit}: { commit: any}) {
            commit('freshTimestampSteps')
        }
    },
})

export const traceAggregatorTimestampPeriodStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorTimestampPeriodStore(): Store<State> {
    return baseUseStore(traceAggregatorTimestampPeriodStoreInjectionKey)
}
