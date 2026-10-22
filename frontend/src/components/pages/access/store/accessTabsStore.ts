import {defineStore} from "pinia";

export const accessTabs = {
    users: 'users',
    services: 'services',
}

interface AccessTabsStoreInterface {
    currentTab: string,
}

export const useAccessTabsStore = defineStore('accessTabsStore', {
    state: (): AccessTabsStoreInterface => {
        return {
            currentTab: accessTabs.users,
        }
    },
    actions: {
        setCurrentTab(tab: string) {
            this.currentTab = tab
        }
    },
})
