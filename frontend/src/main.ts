// @ts-ignore // todo
import App from './App.vue'
import {createApp} from "vue";
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import {authStore, authStoreInjectionKey} from "./store/authStore.ts";
import 'element-plus/theme-chalk/dark/css-vars.css'
import {router} from "./utils/router.ts";
import {traceAggregatorStore, traceAggregatorStoreInjectionKey} from "./components/pages/trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import {
    traceAggregatorFindTagsStore,
    traceAggregatorFindTagsStoreInjectionKey
} from "./components/pages/trace-aggregator/components/tags/store/traceAggregatorTagsStore.ts";
import {traceAggregatorTreeStore, traceAggregatorTreeStoreInjectionKey} from "./components/pages/trace-aggregator/components/tree/store/traceAggregatorTreeStore.ts";
import {traceAggregatorTabsStore, traceAggregatorTabsStoreInjectionKey} from "./components/pages/trace-aggregator/store/traceAggregatorTabsStore.ts";
import {traceAggregatorDataStore, traceAggregatorDataStoreInjectionKey} from "./components/pages/trace-aggregator/components/trace/store/traceAggregatorDataStore.ts";
import {
    traceAggregatorServicesStore,
    traceAggregatorServicesStoreInjectionKey
} from "./components/pages/trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";
import {
    traceAggregatorProfilingStore,
    traceAggregatorProfilingStoreInjectionKey
} from "./components/pages/trace-aggregator/components/profiling/store/traceAggregatorProfilingStore.ts";
import {toolLinksStore, toolLinksStoreInjectionKey} from "./store/toolLinksStore.ts";
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
    .use(traceAggregatorServicesStore, traceAggregatorServicesStoreInjectionKey)
    .mount('#app')
