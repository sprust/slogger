// @ts-ignore // todo
import App from './App.vue'
import {createApp} from "vue";
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import {authStore, authStoreInjectionKey} from "./store/authStore.ts";
import 'element-plus/theme-chalk/dark/css-vars.css'
import {router} from "./utils/router.ts";
import {traceAggregatorStore, traceAggregatorStoreInjectionKey} from "./components/pages/trace-aggregator/store/traceAggregatorStore.ts";
import {
    traceAggregatorFindTagsStore,
    traceAggregatorFindTagsStoreInjectionKey
} from "./components/pages/trace-aggregator/store/traceAggregatorTagsStore.ts";
import {traceAggregatorTreeStore, traceAggregatorTreeStoreInjectionKey} from "./components/pages/trace-aggregator/store/traceAggregatorTreeStore.ts";
import {traceAggregatorTabsStore, traceAggregatorTabsStoreInjectionKey} from "./components/pages/trace-aggregator/store/traceAggregatorTabsStore.ts";
import {traceAggregatorDataStore, traceAggregatorDataStoreInjectionKey} from "./components/pages/trace-aggregator/store/traceAggregatorDataStore.ts";
import {
    traceAggregatorServicesStore,
    traceAggregatorServicesStoreInjectionKey
} from "./components/pages/trace-aggregator/store/traceAggregatorServicesStore.ts";
import {
    traceAggregatorProfilingStore,
    traceAggregatorProfilingStoreInjectionKey
} from "./components/pages/trace-aggregator/store/traceAggregatorProfilingStore.ts";
import {traceAggregatorGraphStore, traceAggregatorGraphStoreInjectionKey} from "./components/pages/trace-aggregator/store/traceAggregatorGraphStore.ts";
import {
    traceAggregatorTimestampPeriodStore,
    traceAggregatorTimestampPeriodStoreInjectionKey
} from "./components/pages/trace-aggregator/store/traceAggregatorTimestampPeriodsStore.ts";
import {
    traceAggregatorTimestampFieldsStore,
    traceAggregatorTimestampFieldsStoreInjectionKey
} from "./components/pages/trace-aggregator/store/traceAggregatorTimestampFieldsStore.ts";
import {toolLinksStore, toolLinksStoreInjectionKey} from "./store/toolLinksStore.ts";
import {traceDynamicIndexesStore, traceDynamicIndexesInjectionKey} from "./components/pages/trace-aggregator/store/traceDynamicIndexesStore.ts";
import {traceAdminStoresStore, traceAdminStoresStoreInjectionKey} from "./components/pages/trace-aggregator/store/traceAdminStoresStore.ts";
import {createPinia} from "pinia";

const pinia = createPinia()

createApp(App)
    .use(router)
    .use(ElementPlus)
    .use(pinia)
    //
    .use(authStore, authStoreInjectionKey)
    .use(toolLinksStore, toolLinksStoreInjectionKey)
    .use(traceAggregatorStore, traceAggregatorStoreInjectionKey)
    .use(traceAggregatorFindTagsStore, traceAggregatorFindTagsStoreInjectionKey)
    .use(traceAggregatorTreeStore, traceAggregatorTreeStoreInjectionKey)
    .use(traceAggregatorProfilingStore, traceAggregatorProfilingStoreInjectionKey)
    .use(traceAggregatorTabsStore, traceAggregatorTabsStoreInjectionKey)
    .use(traceAggregatorDataStore, traceAggregatorDataStoreInjectionKey)
    .use(traceAggregatorGraphStore, traceAggregatorGraphStoreInjectionKey)
    .use(traceAggregatorTimestampPeriodStore, traceAggregatorTimestampPeriodStoreInjectionKey)
    .use(traceAggregatorTimestampFieldsStore, traceAggregatorTimestampFieldsStoreInjectionKey)
    .use(traceAggregatorServicesStore, traceAggregatorServicesStoreInjectionKey)
    .use(traceDynamicIndexesStore, traceDynamicIndexesInjectionKey)
    .use(traceAdminStoresStore, traceAdminStoresStoreInjectionKey)
    .mount('#app')
