<template>
  <div class="landing">
    <el-row class="landing-top-bar" align="middle">
      <el-space :size="12">
        <el-text tag="b">{{ text.topBar.title }}</el-text>
        <el-text type="info">{{ text.topBar.subtitle }}</el-text>
      </el-space>
      <div class="flex-grow"/>
      <el-segmented v-model="language" :options="languages" size="small"/>
      <el-button :icon="isDark ? Moon : Sunny" link class="landing-action" @click="toggleDark"/>
      <el-link
          href="https://github.com/sprust/slogger"
          target="_blank"
          rel="noopener"
          :underline="false"
          class="landing-action"
      >
        {{ text.topBar.github }}
      </el-link>
      <el-button class="landing-action" @click="signIn">{{ text.topBar.login }}</el-button>
    </el-row>

    <LandingLoginDialog v-model="loginVisible"/>

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
import {LandingLanguage, landingLocale, landingText, setLandingLanguage} from "./content/locale.ts";
import type {LandingText} from "./content/types.ts";
import LandingLoginDialog from "./LandingLoginDialog.vue";
import {ApiTokenStorage} from "../../../utils/apiContainer.ts";
import {defaultRouteName} from "../../../utils/router.ts";
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
    LandingLoginDialog,
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
      loginVisible: false,
      isDark,
      toggleDarkUsing: useToggle(isDark),
    }
  },

  computed: {
    text(): LandingText {
      return landingText()
    },
    language: {
      get(): LandingLanguage {
        return landingLocale.language
      },
      set(language: LandingLanguage) {
        setLandingLanguage(language)
      },
    },
    languages(): Array<{ label: string, value: LandingLanguage }> {
      return [
        {label: 'EN', value: 'en'},
        {label: 'RU', value: 'ru'},
      ]
    },
    Sunny() {
      return Sunny
    },
    Moon() {
      return Moon
    },
  },

  watch: {
    '$route.query.login': {
      immediate: true,
      handler(value: unknown) {
        if (value) {
          this.loginVisible = true
        }
      },
    },
    loginVisible(visible: boolean) {
      if (!visible && this.$route.query.login) {
        this.$router.replace({name: this.$route.name ?? undefined, query: {}})
      }
    },
  },

  methods: {
    signIn() {
      if (ApiTokenStorage.getToken()) {
        this.$router.push({name: defaultRouteName})

        return
      }

      this.loginVisible = true
    },
    toggleDark() {
      this.toggleDarkUsing()
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

.landing-action {
  margin-left: 16px;
}

.flex-grow {
  flex-grow: 1;
}
</style>
