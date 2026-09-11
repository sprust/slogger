<template>
  <div class="dashboard-metrics" v-loading="servicesStore.loading">
    <el-row align="middle" class="metrics-toolbar">
      <el-text type="info">
        New traces per 15 minutes over the last 24 hours, UTC
      </el-text>
      <div class="flex-grow"/>
      <el-space>
        <el-select
            v-model="metricsStore.selectedServiceIds"
            :placeholder="`Services (${servicesStore.items.length})`"
            style="width: 300px"
            collapse-tags
            :max-collapse-tags="2"
            clearable
            filterable
            multiple
        >
          <el-option
              v-for="service in servicesStore.items"
              :key="service.id"
              :label="service.name"
              :value="service.id"
          />
        </el-select>
        <el-button
            :icon="IconRefresh"
            :disabled="metricsStore.busy || !visibleServices.length"
            @click="refreshAll"
        >
          Refresh all
        </el-button>
        <el-button
            :icon="IconRefresh"
            :disabled="metricsStore.busy || !hasLoadedCharts"
            @click="refreshLoaded"
        >
          Refresh loaded
        </el-button>
      </el-space>
    </el-row>
    <el-text v-if="servicesStore.loaded && !servicesStore.items.length" type="info">
      No services.
    </el-text>
    <DashboardMetricsServiceChart
        v-for="service in visibleServices"
        :key="service.id"
        :service="service"
    />
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {Refresh as IconRefresh} from '@element-plus/icons-vue'
import DashboardMetricsServiceChart from "./DashboardMetricsServiceChart.vue";
import {initialChartsCount, useDashboardMetricsStore} from "./store/dashboardMetricsStore.ts";
import {
  TraceAggregatorService,
  useTraceAggregatorServicesStore
} from "../trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";

export default defineComponent({
  components: {DashboardMetricsServiceChart},

  computed: {
    metricsStore() {
      return useDashboardMetricsStore()
    },
    servicesStore() {
      return useTraceAggregatorServicesStore()
    },
    IconRefresh() {
      return IconRefresh
    },
    visibleServices(): Array<TraceAggregatorService> {
      const selected = this.metricsStore.selectedServiceIds

      if (!selected.length) {
        return this.servicesStore.items
      }

      return this.servicesStore.items.filter((service: TraceAggregatorService) => selected.includes(service.id))
    },
    visibleServiceIds(): Array<number> {
      return this.visibleServices.map((service: TraceAggregatorService) => service.id)
    },
    hasLoadedCharts(): boolean {
      return this.visibleServiceIds.some((serviceId: number) => this.metricsStore.charts[serviceId]?.loaded)
    },
  },

  methods: {
    refreshAll() {
      this.metricsStore.loadSequentially(this.visibleServiceIds)
    },
    refreshLoaded() {
      this.metricsStore.refreshLoaded(this.visibleServiceIds)
    },
    async loadInitialCharts() {
      if (this.metricsStore.initialized) {
        return
      }

      this.metricsStore.initialized = true

      if (!this.servicesStore.loaded) {
        await this.servicesStore.findServices()
      }

      await this.metricsStore.loadSequentially(
          this.servicesStore.items
              .slice(0, initialChartsCount)
              .map((service: TraceAggregatorService) => service.id)
      )
    },
  },

  mounted() {
    this.loadInitialCharts()
  },
})
</script>

<style scoped>
.dashboard-metrics {
  width: 100%;
  min-height: 200px;
}

.metrics-toolbar {
  padding-bottom: 10px;
}

.flex-grow {
  flex-grow: 1;
}
</style>
