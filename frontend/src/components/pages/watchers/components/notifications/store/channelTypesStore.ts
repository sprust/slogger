import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {currentSession, sessionEnded} from "../../../../../../store/session.ts";
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

            const session = currentSession()

            return await handleApiRequest(
                () => ApiContainer.get().notificationChannelsTypesList()
                    .then(response => {
                        // Signed out while this was on the wire: the answer describes a
                        // session that is over.
                        if (sessionEnded(session)) {
                            return
                        }

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
