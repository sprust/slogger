import {defineStore} from "pinia";

export const manageTabs = {
    users: 'users',
    services: 'services',
    settings: 'settings',
}

interface ManageTabsStoreInterface {
    currentTab: string,
}

export const useManageTabsStore = defineStore('manageTabsStore', {
    state: (): ManageTabsStoreInterface => {
        return {
            currentTab: manageTabs.users,
        }
    },
    actions: {
        setCurrentTab(tab: string) {
            this.currentTab = tab
        }
    },
})
