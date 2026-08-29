import {AdminApi} from "../api-schema/admin-api-schema.ts";
import {ApiContainer, ApiTokenStorage} from "../utils/apiContainer.ts";
import {defineStore} from "pinia";
import {handleApiError, handleApiRequest} from "../utils/handleApiRequest.ts";
import {useSconcurStore} from "../components/pages/sconcur/store/sconcurStore.ts";

type AuthUser = AdminApi.AuthMeList.ResponseBody['data']

interface AuthStoreInterface {
    user: AuthUser | null
}

export const useAuthStore = defineStore('authStore', {
    state: (): AuthStoreInterface => {
        return {
            user: null
        }
    },
    actions: {
        async login(email: string, password: string) {
            return await handleApiRequest(
                () => ApiContainer.get()
                    .authLoginCreate({
                        email: email,
                        password: password
                    })
                    .then((response) => {
                        this.setUser(response.data.data)
                    })
            )
        },
        async auth() {
            if (!ApiTokenStorage.getToken()) {
                this.setUser(null)

                return
            }

            try {
                const response = await ApiContainer.get().authMeList()

                this.setUser(response.data.data)
            } catch (error: any) {
                if (error?.status === 401) {
                    this.setUser(null)

                    return
                }

                handleApiError(error)
            }
        },
        async logout() {
            // The Sconcur page's polling loop and its history live in a store of their own
            // so they survive navigation. This is where a session ends — the button, a
            // 401, the router guard all come through here — so this is where they stop.
            useSconcurStore().reset()

            this.setUser(null)
        },
        setUser(user: AuthUser | null) {
            this.user = user

            if (user === null) {
                ApiTokenStorage.forgetToken()
            } else {
                ApiTokenStorage.setToken(user.api_token)
            }
        }
    },
})
