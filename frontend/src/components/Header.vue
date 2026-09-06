<template>
  <el-menu id="header-menu" mode="horizontal" :default-active="router.currentRoute.path" router>
    <el-menu-item :index="routes.dashboard.path">
      Dashboard
    </el-menu-item>
    <el-menu-item :index="routes.traceAggregator.path">
      Aggregator
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
    <div class="ws-status">
      <ws-status-indicator/>
    </div>
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
    logout() {
      this.authStore.logout()

      this.router.push(this.routes.login)
    }
  },
  mounted() {
    if (!this.toolLinksStore.loaded) {
      this.toolLinksStore.findToolLinks()
    }
  }
})

</script>

<style scoped>
.flex-grow {
  flex-grow: 1;
}

/* The geometry of the menu items beside it — full height, the same transparent bottom
   border they reserve for the active underline, contents centred — so the dot sits on
   their line rather than near it. Not an el-menu-item itself: there is nothing to click
   here, and one would bring a cursor, a hover and a focus ring with it. Narrower, too:
   an indicator standing in a row of buttons should not take a button's width. */
.ws-status {
  display: flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
  height: 100%;
  padding: 0 12px;
  border-bottom: 2px solid transparent;
}

#header-menu {
  height: 50px;
}
</style>
