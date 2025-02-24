import App from './App.vue'
import {createApp} from "vue";
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import 'element-plus/theme-chalk/dark/css-vars.css'
import {router} from "./utils/router.ts";
import {createPinia} from "pinia";

const pinia = createPinia()

createApp(App)
    .use(router)
    .use(ElementPlus)
    .use(pinia)
    .mount('#app')
