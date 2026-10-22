import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../utils/handleApiRequest.ts";

export type User = AdminApi.UsersList.ResponseBody['data'][number];
export type UserCreateBody = AdminApi.UsersCreate.RequestBody;
export type UserUpdateBody = AdminApi.UsersPartialUpdate.RequestBody;

interface UsersStoreInterface {
    loading: boolean
    loaded: boolean
    items: Array<User>
}

export const useUsersStore = defineStore('usersStore', {
    state: (): UsersStoreInterface => {
        return {
            loading: false,
            loaded: false,
            items: [],
        }
    },
    actions: {
        async findUsers() {
            this.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().usersList()
                    .then(response => {
                        this.items = response.data.data
                        this.loaded = true
                    })
            ).finally(() => {
                this.loading = false
            })
        },
        async create(body: UserCreateBody): Promise<boolean> {
            return await handleApiRequest(
                () => ApiContainer.get().usersCreate(body)
                    .then(async () => {
                        await this.findUsers()

                        return true
                    })
            ) === true
        },
        async rename(id: number, body: UserUpdateBody): Promise<boolean> {
            return await handleApiRequest(
                () => ApiContainer.get().usersPartialUpdate(id, body)
                    .then(response => {
                        this.items = this.items.map((user: User) => user.id === id ? response.data.data : user)

                        return true
                    })
            ) === true
        },
    },
})
