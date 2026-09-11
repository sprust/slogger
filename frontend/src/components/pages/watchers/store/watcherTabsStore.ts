import {defineStore} from "pinia";

export const watcherTabs = {
    incidents: 'incidents',
    settings: 'settings',
    channels: 'channels',
}

interface WatcherTabsStoreInterface {
    currentTab: string,
}

/** Kept in a store so that leaving the page and coming back lands on the same tab. */
export const useWatcherTabsStore = defineStore('watcherTabsStore', {
    state: (): WatcherTabsStoreInterface => {
        return {
            currentTab: watcherTabs.incidents,
        }
    },
    actions: {
        setCurrentTab(tab: string) {
            this.currentTab = tab
        }
    },
})
