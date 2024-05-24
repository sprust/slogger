<template>
  <Bar style="height: 50vh" :data="store.state.graphData" :options="store.state.graphOptions" />
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import {useTraceAggregatorGraphStore} from "../../../../store/traceAggregatorGraphStore.ts";

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

export default defineComponent({
  components: {
    Bar,
  },
  data() {
    return {
      store: useTraceAggregatorGraphStore(),
    }
  },
  methods: {
    update() {
      this.store.dispatch('findMetrics')

      if (this.store.state.showGraph) {
        setTimeout(() => {
          this.update()
        }, 1000)
      }
    }
  },
  mounted() {
    this.store.state.payload = {
      timestamp_period: "minute5"
    }

    this.update()
  }
})
</script>

<style scoped>
</style>
