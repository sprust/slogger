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
    <div class="flex-grow"/>
    <el-menu-item index="" @click="toggleDark">
      <el-button :icon="isDark ? Moon : Sunny" link/>
    </el-menu-item>
    <el-menu-item v-for="toolLink in toolLinksStore.state.toolLinks" index="">
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
import {Sunny, Moon} from '@element-plus/icons-vue'
import {useToolLinksStore} from "../store/toolLinksStore.ts";

export default defineComponent({
  data() {
    const isDark = useDark({
      storageKey: 'slogger-dark-mode',
    })

    return {
      router: useRouter(),
      authStore: useAuthStore(),
      toolLinksStore: useToolLinksStore(),
      routes: routes,
      isDark,
      toggleDarkUsing: useToggle(isDark)
    }
  },
  computed: {
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
      this.authStore.dispatch('logout')
      this.router.push(this.routes.login)
    }
  },
  mounted() {
    if (!this.toolLinksStore.state.loaded) {
      this.toolLinksStore.dispatch('findToolLinks')
    }
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
