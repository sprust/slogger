import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

export type ChannelType = AdminApi.NotificationChannelsTypesList.ResponseBody['data'][number];
export type ChannelTypeField = ChannelType['fields'][number];

interface ChannelTypesStoreInterface {
    loading: boolean
    loaded: boolean
    items: Array<ChannelType>
}

export const useChannelTypesStore = defineStore('notificationChannelTypesStore', {
    state: (): ChannelTypesStoreInterface => {
        return {
            loading: false,
            loaded: false,
            items: [] as Array<ChannelType>,
        }
    },
    getters: {
        byType(state): (type: string) => ChannelType | undefined {
            return (type: string) => state.items.find((item: ChannelType) => item.type === type)
        },
        titleOf(state): (type: string) => string {
            return (type: string) => state.items.find((item: ChannelType) => item.type === type)?.title ?? type
        },
    },
    actions: {
        async find() {
            this.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().notificationChannelsTypesList()
                    .then(response => {
                        this.items = response.data.data

                        this.loaded = true
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
    },
})
