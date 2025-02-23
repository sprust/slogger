import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {handleApiError} from "../../../../utils/helpers.ts";

export type TraceTimestampField = AdminApi.TraceAggregatorTraceMetricsFieldsList.ResponseBody['data'][number]

interface State {
    loaded: boolean,
    timestampFields: Array<TraceTimestampField>
    selectedTimestampFields: Array<string>,
}

export const traceAggregatorTimestampFieldsStore = createStore<State>({
    state: {
        loaded: false,
        timestampFields: new Array<TraceTimestampField>(),
        selectedTimestampFields: new Array<string>(),
    } as State,
    mutations: {
        setTimestampFields(state: State, fields: Array<TraceTimestampField>) {
            state.timestampFields = fields
            state.selectedTimestampFields = [state.timestampFields[0].value]
        },
    },
    actions: {
        findTimestampFields({commit, state}: { commit: any, state: State }) {
            ApiContainer.get().traceAggregatorTraceMetricsFieldsList()
                .then(response => {
                    commit('setTimestampFields', response.data.data)

                    state.loaded = true
                })
                .catch((error) => {
                    handleApiError(error)
                })
        },
    },
})

export const traceAggregatorTimestampFieldsStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorTimestampFieldsStore(): Store<State> {
    return baseUseStore(traceAggregatorTimestampFieldsStoreInjectionKey)
}
