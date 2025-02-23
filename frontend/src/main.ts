// @ts-ignore // todo
import App from './App.vue'
import {createApp} from "vue";
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import {authStore, authStoreInjectionKey} from "./store/authStore.ts";
import 'element-plus/theme-chalk/dark/css-vars.css'
import {router} from "./utils/router.ts";
import {traceAggregatorStore, traceAggregatorStoreInjectionKey} from "./components/pages/trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import {traceAggregatorTabsStore, traceAggregatorTabsStoreInjectionKey} from "./components/pages/trace-aggregator/store/traceAggregatorTabsStore.ts";
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
    .use(traceAggregatorProfilingStore, traceAggregatorProfilingStoreInjectionKey)
    .use(traceAggregatorTabsStore, traceAggregatorTabsStoreInjectionKey)
    .mount('#app')
