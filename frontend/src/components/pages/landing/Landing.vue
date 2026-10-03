<template>
  <div class="landing">
    <el-row class="landing-top-bar" align="middle">
      <el-space :size="12">
        <el-text tag="b">{{ text.topBar.title }}</el-text>
        <el-text type="info">{{ text.topBar.subtitle }}</el-text>
      </el-space>
      <div class="flex-grow"/>
      <el-space :size="4" wrap>
        <el-button
            v-for="item in text.topBar.nav"
            :key="item.anchor"
            link
            @click="scrollTo(item.anchor)"
        >
          {{ item.label }}
        </el-button>
      </el-space>
      <el-button :icon="isDark ? Moon : Sunny" link class="landing-theme" @click="toggleDark"/>
      <router-link to="/login" class="landing-login">
        <el-button type="primary">{{ text.topBar.login }}</el-button>
      </router-link>
    </el-row>

    <LandingAbout/>
    <LandingDataPath/>
    <LandingSearch/>
    <LandingTraceTree/>
    <LandingGraphs/>
    <LandingMetrics/>
    <LandingWatchers/>
    <LandingMcp/>
    <LandingStack/>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useToggle} from '@vueuse/shared'
import {useDark} from '@vueuse/core'
import {Moon, Sunny} from '@element-plus/icons-vue'
import {landingText} from "./content/ru.ts";
import LandingAbout from "./sections/LandingAbout.vue";
import LandingDataPath from "./sections/LandingDataPath.vue";
import LandingTraceTree from "./sections/LandingTraceTree.vue";
import LandingSearch from "./sections/LandingSearch.vue";
import LandingGraphs from "./sections/LandingGraphs.vue";
import LandingMetrics from "./sections/LandingMetrics.vue";
import LandingWatchers from "./sections/LandingWatchers.vue";
import LandingMcp from "./sections/LandingMcp.vue";
import LandingStack from "./sections/LandingStack.vue";

export default defineComponent({
  components: {
    LandingAbout,
    LandingDataPath,
    LandingTraceTree,
    LandingSearch,
    LandingGraphs,
    LandingMetrics,
    LandingWatchers,
    LandingMcp,
    LandingStack,
  },

  data() {
    const isDark = useDark({
      storageKey: 'slogger-dark-mode',
    })

    return {
      isDark,
      toggleDarkUsing: useToggle(isDark),
    }
  },

  computed: {
    text() {
      return landingText
    },
    Sunny() {
      return Sunny
    },
    Moon() {
      return Moon
    },
  },

  methods: {
    toggleDark() {
      this.toggleDarkUsing()
    },
    scrollTo(anchor: string) {
      document.getElementById(anchor)?.scrollIntoView({behavior: 'smooth', block: 'start'})
    },
  },
})
</script>

<style scoped>
.landing {
  max-width: 1280px;
  margin: 0 auto;
}

.landing-top-bar {
  padding: 16px 0;
  gap: 12px;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.landing-theme {
  margin-left: 12px;
}

.landing-login {
  margin-left: 12px;
}

.flex-grow {
  flex-grow: 1;
}
</style>
