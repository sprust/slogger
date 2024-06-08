import App from './App.vue'
import {createApp} from "vue";
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import {authStore, authStoreInjectionKey} from "./store/authStore.ts";
import 'element-plus/theme-chalk/dark/css-vars.css'
import {router} from "./utils/router.ts";
import {traceAggregatorStore, traceAggregatorStoreInjectionKey} from "./store/traceAggregatorStore.ts";
import {
    traceAggregatorFindTagsStore,
    traceAggregatorFindTagsStoreInjectionKey
} from "./store/traceAggregatorTagsStore.ts";
import {traceAggregatorTreeStore, traceAggregatorTreeStoreInjectionKey} from "./store/traceAggregatorTreeStore.ts";
import {traceAggregatorTabsStore, traceAggregatorTabsStoreInjectionKey} from "./store/traceAggregatorTabsStore.ts";
import {traceAggregatorDataStore, traceAggregatorDataStoreInjectionKey} from "./store/traceAggregatorDataStore.ts";
import {dashboardDatabaseStore, dashboardDatabaseStoreInjectionKey} from "./store/dashboardDatabaseStore.ts";
import {
    traceAggregatorServicesStore,
    traceAggregatorServicesStoreInjectionKey
} from "./store/traceAggregatorServicesStore.ts";
import {traceCleanerStore, traceCleanerStoreInjectionKey} from "./store/traceCleanerStore.ts";
import {dashboardServiceStatStore, dashboardServiceStatStoreInjectionKey} from "./store/dashboardServiceStatStore.ts";
import {
    traceAggregatorProfilingStore,
    traceAggregatorProfilingStoreInjectionKey
} from "./store/traceAggregatorProfilingStore.ts";
import {traceAggregatorGraphStore, traceAggregatorGraphStoreInjectionKey} from "./store/traceAggregatorGraphStore.ts";
import {
    traceAggregatorTimestampPeriodStore,
    traceAggregatorTimestampPeriodStoreInjectionKey
} from "./store/traceAggregatorTimestampPeriodsStore.ts";
import {
    traceAggregatorTimestampFieldsStore,
    traceAggregatorTimestampFieldsStoreInjectionKey
} from "./store/traceAggregatorTimestampFieldsStore.ts";

createApp(App)
    .use(router)
    .use(ElementPlus)
    .use(authStore, authStoreInjectionKey)
    .use(traceAggregatorStore, traceAggregatorStoreInjectionKey)
    .use(traceAggregatorFindTagsStore, traceAggregatorFindTagsStoreInjectionKey)
    .use(traceAggregatorTreeStore, traceAggregatorTreeStoreInjectionKey)
    .use(traceAggregatorProfilingStore, traceAggregatorProfilingStoreInjectionKey)
    .use(traceAggregatorTabsStore, traceAggregatorTabsStoreInjectionKey)
    .use(traceAggregatorDataStore, traceAggregatorDataStoreInjectionKey)
    .use(traceAggregatorGraphStore, traceAggregatorGraphStoreInjectionKey)
    .use(traceAggregatorTimestampPeriodStore, traceAggregatorTimestampPeriodStoreInjectionKey)
    .use(traceAggregatorTimestampFieldsStore, traceAggregatorTimestampFieldsStoreInjectionKey)
    .use(dashboardDatabaseStore, dashboardDatabaseStoreInjectionKey)
    .use(dashboardServiceStatStore, dashboardServiceStatStoreInjectionKey)
    .use(traceAggregatorServicesStore, traceAggregatorServicesStoreInjectionKey)
    .use(traceCleanerStore, traceCleanerStoreInjectionKey)
    .mount('#app')
