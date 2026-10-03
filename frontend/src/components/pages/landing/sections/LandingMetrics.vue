<template>
  <LandingSection anchor="metrics" :title="text.title" :paragraphs="text.paragraphs">
    <p class="landing-caption">
      <el-text type="info">{{ text.demoCaption }}</el-text>
    </p>
    <el-card class="metrics-card" shadow="never">
      <template #header>
        <el-row align="middle" class="metrics-header">
          <el-text tag="b">billing</el-text>
          <el-text type="info">
            logged {{ totals.logged }} · buffered {{ totals.buffered }} · stored {{ totals.stored }}
          </el-text>
        </el-row>
      </template>
      <div class="metrics-chart">
        <TraceMetricsChart :slots="slots" :rows="rows"/>
      </div>
    </el-card>
  </LandingSection>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import LandingSection from "./LandingSection.vue";
import TraceMetricsChart from "../../dashboard/TraceMetricsChart.vue";
import type {DashboardTraceMetric} from "../../dashboard/store/dashboardMetricsStore.ts";
import {makeMetricTotals, makeMetricValues, MetricTotals} from "../../dashboard/traceMetricsValues.ts";
import type {LandingMetricsText} from "../content/types.ts";
import {landingText} from "../content/locale.ts";
import {demoMetricRows, demoMetricSlots} from "../demo/demoMetrics.ts";

export default defineComponent({
  components: {LandingSection, TraceMetricsChart},

  computed: {
    text(): LandingMetricsText {
      return landingText().metrics
    },
    slots(): Array<string> {
      return demoMetricSlots
    },
    rows(): Array<DashboardTraceMetric> {
      return demoMetricRows
    },
    totals(): MetricTotals {
      return makeMetricTotals(makeMetricValues(this.slots, this.rows))
    },
  },
})
</script>

<style scoped>
.landing-caption {
  margin: 16px 0 8px 0;
}

.metrics-header {
  gap: 10px;
}

.metrics-chart {
  position: relative;
  height: 260px;
}
</style>
