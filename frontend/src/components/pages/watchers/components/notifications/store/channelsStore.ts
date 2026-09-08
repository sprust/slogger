import {ApiContainer} from "../../../../../../utils/apiContainer.ts";
import {AdminApi} from "../../../../../../api-schema/admin-api-schema.ts";
import {defineStore} from "pinia";
import {handleApiRequest} from "../../../../../../utils/handleApiRequest.ts";

export type Channel = AdminApi.NotificationChannelsList.ResponseBody['data'][number];
export type SendResult = AdminApi.NotificationChannelsTestCreate.ResponseBody['data'];

/** What every type's create and edit body looks like above its settings. */
export type ChannelPayload = AdminApi.NotificationChannelsTelegramCreate.RequestBody

/**
 * The settings of one channel, read back. A secret comes as a mask and never as itself.
 */
export type ChannelSettings = {
    id: number,
    bot_token_mask?: string,
    chat_id?: string,
}

const typeEndpoints: Record<string, {
    show: (id: number) => Promise<{ data: { data: ChannelSettings } }>,
    create: (payload: ChannelPayload) => Promise<unknown>,
    update: (id: number, payload: ChannelPayload) => Promise<unknown>,
}> = {
    telegram: {
        show: id => ApiContainer.get().notificationChannelsTelegramDetail(id),
        create: payload => ApiContainer.get().notificationChannelsTelegramCreate(payload as any),
        update: (id, payload) => ApiContainer.get().notificationChannelsTelegramPartialUpdate(id, payload as any),
    },
}

export function channelTypeIsKnown(type: string): boolean {
    return type in typeEndpoints
}

interface ChannelsStoreInterface {
    loading: boolean
    loaded: boolean
    items: Array<Channel>
}

export const useChannelsStore = defineStore('notificationChannelsStore', {
    state: (): ChannelsStoreInterface => {
        return {
            loading: false,
            loaded: false,
            items: [] as Array<Channel>,
        }
    },
    actions: {
        async find() {
            this.loading = true

            return await handleApiRequest(
                () => ApiContainer.get().notificationChannelsList()
                    .then(response => {
                        this.items = response.data.data

                        this.loaded = true
                    })
                    .finally(() => {
                        this.loading = false
                    })
            )
        },
        async findSettings(type: string, id: number): Promise<ChannelSettings | null> {
            const endpoints = typeEndpoints[type]

            if (!endpoints) {
                return null
            }

            return await handleApiRequest(
                () => endpoints.show(id).then(response => response.data.data)
            )
        },
        async create(type: string, payload: ChannelPayload): Promise<boolean> {
            const endpoints = typeEndpoints[type]

            if (!endpoints) {
                return false
            }

            return await handleApiRequest(
                () => endpoints.create(payload).then(() => this.find()).then(() => true)
            ) === true
        },
        async update(type: string, id: number, payload: ChannelPayload): Promise<boolean> {
            const endpoints = typeEndpoints[type]

            if (!endpoints) {
                return false
            }

            return await handleApiRequest(
                () => endpoints.update(id, payload).then(() => this.find()).then(() => true)
            ) === true
        },
        async test(id: number): Promise<SendResult | null> {
            return await handleApiRequest(
                () => ApiContainer.get().notificationChannelsTestCreate(id)
                    .then(response => response.data.data)
            ) ?? null
        },
        async remove(id: number) {
            return await handleApiRequest(
                () => ApiContainer.get().notificationChannelsDelete(id)
                    .then(() => {
                        this.items = this.items.filter((channel: Channel) => channel.id !== id)
                    })
            )
        },
    },
})
