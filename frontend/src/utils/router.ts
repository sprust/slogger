import {createRouter, createWebHistory, NavigationGuardNext, RouteLocationNormalized} from "vue-router";
import {useAuthStore} from "../store/authStore.ts";
import Login from "../components/Login.vue";
import Dashboard from "../components/pages/dashboard/Dashboard.vue";
import TraceAggregator from "../components/pages/trace-aggregator/TraceAggregator.vue";
import TraceCleaner from "../components/pages/trace-cleaner/TraceCleaner.vue";
import Logs from "../components/pages/logs-viewer/Logs.vue";

export const routes = {
    login: {
        path: '/login',
        name: 'login',
    },
    traceAggregator: {
        path: '/trace-aggregator',
        name: 'trace-aggregator',
    },
    dashboard: {
        path: '/dashboard',
        name: 'dashboard',
    },
    traceCleaner: {
        path: '/trace-cleaner',
        name: 'trace-cleaner',
    },
    logs: {
        path: '/logs',
        name: 'logs',
    },
}

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: routes.login.path,
            component: Login,
            name: routes.login.name
        },
        {
            path: routes.dashboard.path,
            component: Dashboard,
            name: routes.dashboard.name
        },
        {
            path: routes.traceAggregator.path,
            component: TraceAggregator,
            name: routes.traceAggregator.name
        },
        {
            path: routes.traceCleaner.path,
            component: TraceCleaner,
            name: routes.traceCleaner.name
        },
        {
            path: routes.logs.path,
            component: Logs,
            name: routes.logs.name
        },
    ],
});

export const defaultRouteName: string = routes.dashboard.name

router.beforeEach(async (to: RouteLocationNormalized, from: RouteLocationNormalized, next: NavigationGuardNext) => {
    console.log('route', {from: from.name, to: to.name})

    const authStore = useAuthStore()

    await authStore.dispatch('auth')

    const authorized = !!authStore.state.user

    if (to.name === routes.login.name) {
        if (authorized) {
            next({name: defaultRouteName})

            return
        }
    } else {
        if (!authorized) {
            next({name: routes.login.name})

            return
        }

        if (to.name === undefined) {
            next({name: defaultRouteName})

            return
        }
    }

    next()
})
