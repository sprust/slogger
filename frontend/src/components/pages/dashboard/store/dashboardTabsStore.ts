import {defineStore} from "pinia";

export const dashboardTabs = {
    metrics: 'metrics',
    databases: 'databases',
    sconcur: 'sconcur',
    cleaner: 'cleaner',
}

interface DashboardTabsStoreInterface {
    currentTab: string,
}

export const useDashboardTabsStore = defineStore('dashboardTabsStore', {
    state: (): DashboardTabsStoreInterface => {
        return {
            currentTab: dashboardTabs.metrics,
        }
    },
})
