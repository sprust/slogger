<template>
  <Bar style="height: 50vh" :data="store.state.graphData" :options="store.state.graphOptions"/>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import {Bar} from 'vue-chartjs'
import ChartDataLabels from 'chartjs-plugin-datalabels';

import {useTraceAggregatorGraphStore} from "../../../../store/traceAggregatorGraphStore.ts";
import {useTraceAggregatorTimestampPeriodStore} from "../../../../store/traceAggregatorTimestampPeriodsStore.ts";
import {useTraceAggregatorStore} from "../../../../store/traceAggregatorStore.ts";

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

      if (this.store.state.showGraph) {
        setTimeout(() => {
          this.update()
        }, 2000)
      }
    }
  },
  mounted() {
    this.store.dispatch('setTimestampPeriod', this.storePeriods.state.timestampPeriods[0].value)

    this.update()
  }
})
</script>

<style scoped>
</style>
