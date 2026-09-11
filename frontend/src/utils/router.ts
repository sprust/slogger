import {createRouter, createWebHistory, NavigationGuardNext, RouteLocationNormalized} from "vue-router";
import {useAuthStore} from "../store/authStore.ts";
import Login from "../components/Login.vue";

const Dashboard = () => import("../components/pages/dashboard/Dashboard.vue")
const TraceAggregator = () => import("../components/pages/trace-aggregator/TraceAggregator.vue")
const TraceCleaner = () => import("../components/pages/trace-cleaner/TraceCleaner.vue")
const Logs = () => import("../components/pages/logs-viewer/Logs.vue")
const Watchers = () => import("../components/pages/watchers/Watchers.vue")
const Sconcur = () => import("../components/pages/sconcur/Sconcur.vue")

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
    watchers: {
        path: '/watchers',
        name: 'watchers',
    },
    logs: {
        path: '/logs',
        name: 'logs',
    },
    sconcur: {
        path: '/sconcur',
        name: 'sconcur',
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
            path: routes.watchers.path,
            component: Watchers,
            name: routes.watchers.name
        },
        {
            path: routes.logs.path,
            component: Logs,
            name: routes.logs.name
        },
        {
            path: routes.sconcur.path,
            component: Sconcur,
            name: routes.sconcur.name
        },
    ],
});

export const defaultRouteName: string = routes.dashboard.name

router.beforeEach(async (to: RouteLocationNormalized, from: RouteLocationNormalized, next: NavigationGuardNext) => {
    console.log('route', {from: from.name, to: to.name})

    const authStore = useAuthStore()

    if (!authStore.user) {
        await authStore.auth()
    }

    const authorized = !!authStore.user

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
