<template>
  <el-menu id="header-menu" mode="horizontal" :default-active="router.currentRoute.path" router>
    <el-menu-item :index="routes.dashboard.path">
      Dashboard
    </el-menu-item>
    <el-menu-item :index="routes.traceAggregator.path">
      Aggregator
    </el-menu-item>
    <el-menu-item :index="routes.watchers.path">
      <!-- The badge sits on the word rather than beside it: the menu lays its items out
           in a row, and a badge given a box of its own would widen the item every time a
           watcher speaks. -->
      <el-badge
          :value="watcherIncidentStatStore.openedCount"
          :hidden="watcherIncidentStatStore.openedCount === 0"
          :offset="[8, -8]"
          type="danger"
      >
        Watchers
      </el-badge>
    </el-menu-item>
    <el-menu-item :index="routes.traceCleaner.path">
      Cleaner
    </el-menu-item>
    <el-menu-item :index="routes.logs.path">
      Logs
    </el-menu-item>
    <el-menu-item :index="routes.sconcur.path">
      Sconcur
    </el-menu-item>
    <div class="flex-grow"/>
    <ws-status-indicator/>
    <el-menu-item index="" @click="toggleDark">
      <el-button :icon="isDark ? Moon : Sunny" link/>
    </el-menu-item>
    <el-menu-item v-for="toolLink in toolLinksStore.toolLinks" index="">
      <el-link :underline="false" :href="toolLink.url" target="_blank">{{ toolLink.name }}</el-link>
    </el-menu-item>
    <el-menu-item index="" @click="logout">
      Logout
    </el-menu-item>
  </el-menu>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useRouter} from "vue-router";
import {useAuthStore} from "../store/authStore.ts";
import {routes} from "../utils/router.ts";
import {useToggle} from '@vueuse/shared'
import {useDark} from '@vueuse/core'
import {Moon, Sunny} from '@element-plus/icons-vue'
import {useToolLinksStore} from "../store/toolLinksStore.ts";
import {useWatcherIncidentStatStore} from "../store/watcherIncidentStatStore.ts";
import WsStatusIndicator from "./WsStatusIndicator.vue";

export default defineComponent({
  components: {WsStatusIndicator},

  data() {
    const isDark = useDark({
      storageKey: 'slogger-dark-mode',
    })

    return {
      router: useRouter(),
      routes: routes,
      isDark,
      toggleDarkUsing: useToggle(isDark)
    }
  },

  computed: {
    authStore() {
      return useAuthStore()
    },
    toolLinksStore() {
      return useToolLinksStore()
    },
    watcherIncidentStatStore() {
      return useWatcherIncidentStatStore()
    },
    Sunny() {
      return Sunny
    },
    Moon() {
      return Moon
    }
  },
  methods: {
    toggleDark() {
      this.toggleDarkUsing()
    },
    async logout() {
      // Awaited: the route guard runs auth() on the way out, and a token still in storage
      // would be revalidated — landing back on the dashboard on a session the server has
      // just dropped.
      await this.authStore.logout()

      this.router.push(this.routes.login)
    }
  },
  mounted() {
    if (!this.toolLinksStore.loaded) {
      this.toolLinksStore.findToolLinks()
    }

    // Followed from the header rather than from the watchers page: an incident opened
    // while somebody is reading traces is exactly the one worth noticing.
    this.watcherIncidentStatStore.watch()
  }
})

</script>

<style scoped>
.flex-grow {
  flex-grow: 1;
}

#header-menu {
  height: 50px;
}
</style>
