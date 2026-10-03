<template>
  <LandingSection anchor="graphs" :title="text.title" :paragraphs="text.paragraphs">
    <el-row align="middle" class="landing-filters">
      <el-text type="info">{{ text.filtersCaption }}:</el-text>
      <el-tag v-for="filter in text.filters" :key="filter" type="info">{{ filter }}</el-tag>
    </el-row>
    <p class="landing-caption">
      <el-text type="info">{{ text.demoCaption }}</el-text>
    </p>
    <el-card shadow="never">
      <div v-for="graph in graphs" :key="graph.name" class="landing-graph">
        <el-row>{{ graph.name }}</el-row>
        <TraceTimelineChart :data="graph.data" :options="options" height="300px"/>
      </div>
    </el-card>
  </LandingSection>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import type {ChartOptions} from 'chart.js'
import LandingSection from "./LandingSection.vue";
import TraceTimelineChart from "../../trace-aggregator/components/graph/TraceTimelineChart.vue";
import {LandingGraphsText, landingText} from "../content/ru.ts";
import {DemoTimelineGraph, demoTimelineGraphs, demoTimelineOptions} from "../demo/demoTimeline.ts";

export default defineComponent({
  components: {LandingSection, TraceTimelineChart},

  computed: {
    text(): LandingGraphsText {
      return landingText.graphs
    },
    graphs(): Array<DemoTimelineGraph> {
      return demoTimelineGraphs
    },
    options(): ChartOptions {
      return demoTimelineOptions
    },
  },
})
</script>

<style scoped>
.landing-filters {
  gap: 8px;
  margin-top: 8px;
}

.landing-caption {
  margin: 16px 0 8px 0;
}

.landing-graph + .landing-graph {
  margin-top: 16px;
}
</style>
