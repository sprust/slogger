import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'

export const traceAggregatorTabs = {
    traces: 'traces',
    tree: 'tree',
}

interface State {
    currentTab: string,
}

export const traceAggregatorTabsStore = createStore<State>({
    state: {
        currentTab: traceAggregatorTabs.traces,
    } as State,
    mutations: {
        setCurrentTab(state: State, tab: string) {
            state.currentTab = tab
        },
    },
    actions: {
        setCurrentTab({commit}: { commit: any }, tab: string) {
            commit('setCurrentTab', tab)
        }
    },
})

export const traceAggregatorTabsStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useTraceAggregatorTabsStore(): Store<State> {
    return baseUseStore(traceAggregatorTabsStoreInjectionKey)
}
