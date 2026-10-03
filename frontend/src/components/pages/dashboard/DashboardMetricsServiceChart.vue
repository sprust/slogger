<template>
  <el-card class="metrics-card" shadow="never">
    <template #header>
      <el-row align="middle" class="metrics-header">
        <el-text tag="b">{{ service.name }}</el-text>
        <el-text v-if="chart.loaded" type="info">
          logged {{ totals.logged }} · buffered {{ totals.buffered }} · stored {{ totals.stored }}
        </el-text>
        <div class="flex-grow"/>
        <el-select
            v-model="chart.selectedTypes"
            :placeholder="`Types (${types.length})`"
            :disabled="!chart.loaded"
            style="width: 260px"
            collapse-tags
            :max-collapse-tags="2"
            clearable
            multiple
        >
          <el-option
              v-for="type in types"
              :key="type.name"
              :label="`${type.name} (${type.stored})`"
              :value="type.name"
          />
        </el-select>
        <el-button :icon="IconRefresh" :loading="chart.loading" @click="refresh">
          Refresh
        </el-button>
      </el-row>
    </template>
    <div class="metrics-chart" v-loading="chart.loading">
      <TraceMetricsChart
          v-if="chart.loaded"
          :slots="chart.slots"
          :rows="chart.rows"
          :selected-types="chart.selectedTypes"
          click-hint="Click to open in the aggregator"
          @slot-click="openInAggregator"
      />
      <div v-else-if="!chart.loading" class="metrics-placeholder">
        <el-text type="info">Not loaded. Press Refresh.</el-text>
      </div>
    </div>
  </el-card>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {Refresh as IconRefresh} from '@element-plus/icons-vue'
import TraceMetricsChart from "./TraceMetricsChart.vue";
import {DashboardServiceMetrics, useDashboardMetricsStore} from "./store/dashboardMetricsStore.ts";
import {
  filterMetricRows,
  makeMetricTotals,
  makeMetricTypes,
  makeMetricValues,
  metricSlotMs,
  MetricTotals,
  MetricType
} from "./traceMetricsValues.ts";
import {utcTimestamp} from "../../../utils/helpers.ts";
import {useTraceAggregatorStore} from "../trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import {routes} from "../../../utils/router.ts";
import {TraceAggregatorService} from "../trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";

export default defineComponent({
  components: {TraceMetricsChart},

  props: {
    service: {
      type: Object as PropType<TraceAggregatorService>,
      required: true,
    },
  },

  created() {
    this.metricsStore.chart(this.service.id)
  },

  computed: {
    metricsStore() {
      return useDashboardMetricsStore()
    },
    chart(): DashboardServiceMetrics {
      return this.metricsStore.charts[this.service.id]
    },
    IconRefresh() {
      return IconRefresh
    },
    types(): Array<MetricType> {
      return makeMetricTypes(this.chart.rows)
    },
    totals(): MetricTotals {
      return makeMetricTotals(
          makeMetricValues(this.chart.slots, filterMetricRows(this.chart.rows, this.chart.selectedTypes))
      )
    },
  },

  methods: {
    refresh() {
      this.metricsStore.findServiceMetrics(this.service.id)
    },
    openInAggregator(slotIndex: number) {
      const from = utcTimestamp(this.chart.slots[slotIndex])

      if (from === null) {
        return
      }

      useTraceAggregatorStore().applyExternalFilter({
        serviceIds: [this.service.id],
        types: this.chart.selectedTypes,
        period: {from, to: from + metricSlotMs},
      })

      this.$router.push(routes.traceAggregator)
    },
  },
})
</script>

<style scoped>
.metrics-card {
  margin-bottom: 10px;
}

.metrics-header {
  gap: 10px;
}

.metrics-chart {
  position: relative;
  height: 260px;
}

.metrics-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.flex-grow {
  flex-grow: 1;
}
</style>
