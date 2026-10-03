<template>
  <LandingSection anchor="about" :title="text.title" :paragraphs="text.paragraphs">
    <el-row :gutter="12">
      <el-col v-for="feature in text.features" :key="feature.title" :span="6" class="landing-feature">
        <el-card shadow="never" class="landing-feature-card">
          <template #header>
            <el-text tag="b">{{ feature.title }}</el-text>
          </template>
          <el-text>{{ feature.text }}</el-text>
        </el-card>
      </el-col>
    </el-row>
  </LandingSection>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import LandingSection from "./LandingSection.vue";
import {LandingAboutText, landingText} from "../content/ru.ts";

export default defineComponent({
  components: {LandingSection},

  computed: {
    text(): LandingAboutText {
      return landingText.about
    },
  },
})
</script>

<style scoped>
.landing-feature {
  margin-bottom: 12px;
}

.landing-feature-card {
  height: 100%;
}
</style>
