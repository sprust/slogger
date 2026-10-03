<template>
  <LandingSection anchor="install" :title="text.title" :paragraphs="text.paragraphs">
    <div v-for="step in text.steps" :key="step.title" class="landing-step">
      <h3>{{ step.title }}</h3>
      <p v-if="step.text" class="landing-step-text">
        <el-text>{{ step.text }}</el-text>
      </p>
      <LandingCode v-if="step.code" :code="step.code"/>
    </div>
  </LandingSection>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import LandingSection from "./LandingSection.vue";
import LandingCode from "./LandingCode.vue";
import type {LandingInstallText} from "../content/types.ts";
import {landingText} from "../content/locale.ts";

export default defineComponent({
  components: {LandingSection, LandingCode},

  computed: {
    text(): LandingInstallText {
      return landingText().install
    },
  },
})
</script>

<style scoped>
.landing-step + .landing-step {
  margin-top: 16px;
}

.landing-step-text {
  line-height: 1.6;
}
</style>
