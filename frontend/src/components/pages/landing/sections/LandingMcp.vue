<template>
  <LandingSection anchor="mcp" :title="text.title" :paragraphs="text.paragraphs">
    <p class="landing-caption">
      <el-text type="info">{{ text.scenariosCaption }}</el-text>
    </p>
    <el-tabs v-model="activeScenario" type="border-card">
      <el-tab-pane
          v-for="scenario in scenarios"
          :key="scenario.name"
          :name="scenario.name"
          :label="scenario.title"
      >
        <el-scrollbar class="landing-scenario">
          <p class="landing-question">
            <el-text tag="b">{{ scenario.question }}</el-text>
          </p>
          <el-timeline>
            <el-timeline-item v-for="(step, index) in scenario.steps" :key="index" type="primary" hollow>
              <el-space direction="vertical" alignment="flex-start" :size="4">
                <el-space :size="8" wrap>
                  <el-tag>{{ step.tool }}</el-tag>
                  <el-text v-if="step.params" type="info" class="landing-params">{{ step.params }}</el-text>
                </el-space>
                <el-text>{{ step.result }}</el-text>
              </el-space>
            </el-timeline-item>
          </el-timeline>
          <el-card shadow="never">
            <template #header>
              <el-text tag="b">{{ text.answerCaption }}</el-text>
            </template>
            <el-text>{{ scenario.answer }}</el-text>
          </el-card>
        </el-scrollbar>
      </el-tab-pane>
    </el-tabs>
    <p class="landing-caption">
      <el-text type="info">{{ text.connectCaption }}</el-text>
    </p>
    <el-card shadow="never">
      <pre class="landing-command">{{ text.connectCommand }}</pre>
      <el-text type="info">{{ text.connectNote }}</el-text>
    </el-card>
  </LandingSection>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import LandingSection from "./LandingSection.vue";
import {LandingMcpText, landingText} from "../content/ru.ts";
import {McpScenario, mcpScenarios} from "../content/mcpScenarios.ru.ts";

export default defineComponent({
  components: {LandingSection},

  data() {
    return {
      activeScenario: mcpScenarios[0].name,
    }
  },

  computed: {
    text(): LandingMcpText {
      return landingText.mcp
    },
    scenarios(): Array<McpScenario> {
      return mcpScenarios
    },
  },
})
</script>

<style scoped>
.landing-caption {
  margin: 16px 0 8px 0;
}

.landing-scenario {
  height: 600px;
}

.landing-question {
  margin: 0 0 16px 0;
}

.landing-params {
  font-family: var(--el-font-family-mono, monospace);
}

.landing-command {
  margin: 0 0 8px 0;
  white-space: pre-wrap;
  font-family: var(--el-font-family-mono, monospace);
}
</style>
