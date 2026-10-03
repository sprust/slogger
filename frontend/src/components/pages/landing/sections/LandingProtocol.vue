<template>
  <LandingSection anchor="protocol" :title="text.title" :paragraphs="text.paragraphs">
    <p class="landing-caption">
      <el-text>{{ text.authCaption }}</el-text>
    </p>
    <LandingCode :code="text.authCode"/>
    <p class="landing-caption">
      <el-text>{{ text.messageCaption }}</el-text>
    </p>
    <LandingCode :code="text.messageCode"/>
    <el-row :gutter="16">
      <el-col :span="12">
        <p class="landing-caption">
          <el-text>{{ text.createCaption }}</el-text>
        </p>
        <LandingCode :code="text.createCode"/>
      </el-col>
      <el-col :span="12">
        <p class="landing-caption">
          <el-text>{{ text.updateCaption }}</el-text>
        </p>
        <LandingCode :code="text.updateCode"/>
      </el-col>
    </el-row>
    <ul class="landing-notes">
      <li v-for="note in text.notes" :key="note">
        <el-text>{{ note }}</el-text>
      </li>
    </ul>
  </LandingSection>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import LandingSection from "./LandingSection.vue";
import LandingCode from "./LandingCode.vue";
import type {LandingProtocolText} from "../content/types.ts";
import {landingText} from "../content/locale.ts";

export default defineComponent({
  components: {LandingSection, LandingCode},

  computed: {
    text(): LandingProtocolText {
      return landingText().protocol
    },
  },
})
</script>

<style scoped>
.landing-caption {
  margin: 16px 0 8px 0;
  line-height: 1.6;
}

.landing-notes {
  margin: 16px 0 0 0;
  line-height: 1.6;
}
</style>
