import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, Store, useStore as baseUseStore} from 'vuex'
import {ApiContainer} from "../utils/apiContainer.ts";
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {handleApiError} from "../utils/helpers.ts";

export type ToolsLink = AdminApi.ToolsLinksList.ResponseBody['data'][number];

interface State {
    loaded: boolean
    toolLinks: Array<ToolsLink>
}

export const toolLinksStore = createStore<State>({
    state: {
        loaded: false,
        toolLinks: [] as Array<ToolsLink>
    } as State,
    mutations: {
        setToolLinks(state: State, toolLinks: Array<ToolsLink>) {
            state.toolLinks = toolLinks
        },
    },
    actions: {
        findToolLinks({commit, state}: { commit: any, state: State }) {
            state.loaded = false

            ApiContainer.get().toolsLinksList()
                .then(response => {
                    commit('setToolLinks', response.data.data)
                })
                .catch((error) => {
                    handleApiError(error)
                })
                .finally(() => {
                    state.loaded = true
                })
        }
    },
})

export const toolLinksStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useToolLinksStore(): Store<State> {
    return baseUseStore(toolLinksStoreInjectionKey)
}
