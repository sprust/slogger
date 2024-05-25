<template>
  <Bar
      ref="tracesGraphRef"
      style="min-height: 60vh; max-height: 60vh"
      :data="store.state.graphData"
      :options="store.state.graphOptions"
      @click="onGraphClick"
  />
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  InteractionItem,
  Legend,
  LinearScale,
  Title,
  Tooltip
} from 'chart.js'
import {Bar, getElementAtEvent} from 'vue-chartjs'
import ChartDataLabels from 'chartjs-plugin-datalabels';

import {useTraceAggregatorGraphStore} from "../../../../store/traceAggregatorGraphStore.ts";
import {useTraceAggregatorTimestampPeriodStore} from "../../../../store/traceAggregatorTimestampPeriodsStore.ts";
import {useTraceAggregatorStore} from "../../../../store/traceAggregatorStore.ts";
import {convertDateStringToLocalFull} from "../../../../utils/helpers.ts";

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ChartDataLabels)

export default defineComponent({
  components: {
    Bar,
  },
  data() {
    return {
      store: useTraceAggregatorGraphStore(),
      storePeriods: useTraceAggregatorTimestampPeriodStore(),
      traceStore: useTraceAggregatorStore(),
    }
  },
  methods: {
    update() {
      this.store.state.payload.timestamp_period = this.storePeriods.state.selectedTimestampPeriod
      this.store.state.payload.timestamp_step = this.storePeriods.state.selectedTimestampStep

      this.store.state.payload.service_ids = this.traceStore.state.payload.service_ids
      this.store.state.payload.logging_to = this.traceStore.state.payload.logging_to
          ? this.traceStore.state.payload.logging_to
          : undefined
      this.store.state.payload.types = this.traceStore.state.payload.types
      this.store.state.payload.tags = this.traceStore.state.payload.tags
      this.store.state.payload.statuses = this.traceStore.state.payload.statuses
      this.store.state.payload.duration_from = this.traceStore.state.payload.duration_from
      this.store.state.payload.duration_to = this.traceStore.state.payload.duration_to
      this.store.state.payload.data = this.traceStore.state.payload.data
      this.store.state.payload.has_profiling = this.traceStore.state.payload.has_profiling

      this.store.dispatch('findMetrics')
          .finally(() => {
            if (this.store.state.showGraph) {
              this.traceStore.state.payload.logging_from = this.store.state.loggedAtFrom

              setTimeout(() => {
                this.update()
              }, 1000)
            }
          })
    },
    onGraphClick(mouseEvent: MouseEvent) {
      // @ts-ignore TODO
      const chart = this.$refs.tracesGraphRef?.chart

      if (!chart) {
        return
      }

      const elements: InteractionItem[] = getElementAtEvent(chart, mouseEvent)

      if (!elements.length) {
        return;
      }

      const {index} = elements[0]

      this.traceStore.state.payload.logging_from = new Date(
          convertDateStringToLocalFull(this.store.state.metrics[index].timestamp)
      )
      this.traceStore.state.payload.logging_to = new Date(
          convertDateStringToLocalFull(this.store.state.metrics[index].timestamp_to)
      )

      this.store.state.showGraph = false
    }
  },
  mounted() {
    this.update()
  }
})
</script>

<style scoped>
</style>
