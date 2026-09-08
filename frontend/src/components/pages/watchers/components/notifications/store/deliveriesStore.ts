import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

export type Delivery = AdminApi.NotificationChannelsDeliveriesList.ResponseBody['data'][number];

interface DeliveriesStoreInterface {
    loading: { [channelId: number]: boolean }
    items: { [channelId: number]: Array<Delivery> }
}

export const useDeliveriesStore = defineStore('notificationDeliveriesStore', {
    state: (): DeliveriesStoreInterface => {
        return {
            loading: {},
            items: {},
        }
    },
    actions: {
        async find(channelId: number) {
            this.loading[channelId] = true

            return await handleApiRequest(
                () => ApiContainer.get().notificationChannelsDeliveriesList(channelId)
                    .then(response => {
                        this.items[channelId] = response.data.data
                    })
                    .finally(() => {
                        delete this.loading[channelId]
                    })
            )
        },
    },
})
