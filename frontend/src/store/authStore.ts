import type {InjectionKey} from "vue";
// @ts-ignore // todo
import {createStore, useStore as baseUseStore, Store} from 'vuex'
import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {ApiContainer, ApiTokenStorage} from "../utils/apiContainer.ts";
import {handleApiError} from "../utils/helpers.ts";

type AuthUser = AdminApi.AuthMeList.ResponseBody['data']

interface State {
    user: AuthUser | null
}

export const authStore = createStore<State>({
    state: {
        user: null
    },
    mutations: {
        setUser(state: State, user: AuthUser | null) {
            state.user = user

            if (user === null) {
                ApiTokenStorage.forgetToken()
            } else {
                ApiTokenStorage.setToken(user.api_token)
            }
        }
    },
    actions: {
        login({commit}: { commit: any }, {email, password}: { email: string, password: string }) {
            return ApiContainer.get()
                .authLoginCreate({
                    email: email,
                    password: password
                })
                .then((response) => {
                    commit('setUser', response.data.data)
                })
                .catch((error) => {
                    handleApiError(error)
                })
        },
        async auth({commit}: { commit: any }) {
            if (!ApiTokenStorage.getToken()) {
                commit('setUser', null)

                return
            }

            try {
                const response = await ApiContainer.get().authMeList()

                commit('setUser', response.data.data)
            } catch (error: any) {
                if ('status' in error && error.status === 401) {
                    commit('setUser', null)
                } else {
                    handleApiError(error)
                }
            }
        },
        async logout({commit}: { commit: any }) {
            commit('setUser', null)
        },
    },
})

export const authStoreInjectionKey: InjectionKey<Store<State>> = Symbol()

export function useAuthStore() {
    return baseUseStore(authStoreInjectionKey)
}
