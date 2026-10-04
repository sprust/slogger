import {createRouter, createWebHistory, NavigationGuardNext, RouteLocationNormalized, RouteLocationRaw} from "vue-router";
import {useAuthStore} from "../store/authStore.ts";

const Dashboard = () => import("../components/pages/dashboard/Dashboard.vue")
const TraceAggregator = () => import("../components/pages/trace-aggregator/TraceAggregator.vue")
const Logs = () => import("../components/pages/logs-viewer/Logs.vue")
const Watchers = () => import("../components/pages/watchers/Watchers.vue")
const Mcps = () => import("../components/pages/mcps/Mcps.vue")
const Landing = () => import("../components/pages/landing/Landing.vue")

export const routes = {
    traceAggregator: {
        path: '/trace-aggregator',
        name: 'trace-aggregator',
    },
    dashboard: {
        path: '/dashboard',
        name: 'dashboard',
    },
    watchers: {
        path: '/watchers',
        name: 'watchers',
    },
    logs: {
        path: '/logs',
        name: 'logs',
    },
    mcps: {
        path: '/mcps',
        name: 'mcps',
    },
    landing: {
        path: '/',
        name: 'landing',
    },
}

export const loginRoute: RouteLocationRaw = {name: routes.landing.name, query: {login: '1'}}

export const router = createRouter({
    history: createWebHistory(),
    routes: [
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
            path: routes.mcps.path,
            component: Mcps,
            name: routes.mcps.name
        },
        {
            path: routes.landing.path,
            component: Landing,
            name: routes.landing.name,
            meta: {public: true},
        },
    ],
});

export const defaultRouteName: string = routes.dashboard.name

router.beforeEach(async (to: RouteLocationNormalized, from: RouteLocationNormalized, next: NavigationGuardNext) => {
    console.log('route', {from: from.name, to: to.name})

    if (to.meta.public) {
        next()

        return
    }

    const authStore = useAuthStore()

    if (!authStore.user) {
        await authStore.auth()
    }

    const authorized = !!authStore.user

    if (!authorized) {
        next(loginRoute)

        return
    }

    if (to.name === undefined) {
        next({name: defaultRouteName})

        return
    }

    next()
})
