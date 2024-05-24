<template>
  <Bar style="height: 50vh" :data="store.state.graphData" :options="store.state.graphOptions" />
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
import { Bar } from 'vue-chartjs'

import {useTraceAggregatorGraphStore} from "../../../../store/traceAggregatorGraphStore.ts";
import {useTraceAggregatorTimestampPeriodStore} from "../../../../store/traceAggregatorTimestampPeriodsStore.ts";

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

export default defineComponent({
  components: {
    Bar,
  },
  data() {
    return {
      store: useTraceAggregatorGraphStore(),
      storePeriods: useTraceAggregatorTimestampPeriodStore(),
    }
  },
  methods: {
    update() {
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
