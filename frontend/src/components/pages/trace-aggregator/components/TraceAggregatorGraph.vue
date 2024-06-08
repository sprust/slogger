<template>
  <div v-for="graph in store.state.graphs">
    <el-row>
      {{ graph.name }}
    </el-row>
    <Bar
        ref="tracesGraphRef"
        :style="`min-height: ${graphItemHeight}; max-height: ${graphItemHeight}`"
        :data="graph.data"
        :options="store.state.graphOptions"
        @click="onGraphClick"
    />
  </div>
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

import {useTraceAggregatorGraphStore} from "../../../../store/traceAggregatorGraphStore.ts";
import {useTraceAggregatorTimestampPeriodStore} from "../../../../store/traceAggregatorTimestampPeriodsStore.ts";
import {useTraceAggregatorStore} from "../../../../store/traceAggregatorStore.ts";
import {convertDateStringToLocalFull} from "../../../../utils/helpers.ts";

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

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
  computed: {
    graphItemHeight(): string {
      if (!this.store.state.graphs.length) {
        return '60vh'
      }

      return (80 / this.store.state.graphs.length) + 'vh'
    }
  },
  methods: {
    update() {
      if (this.store.state.waiting) {
        return
      }

      this.traceStore.dispatch('prepareCommonPayloadData')

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

      this.store.dispatch('findMetrics', {dataFields: this.traceStore.state.customFields})
          .finally(() => {
            if (!this.store.state.showGraph) {
              return
            }

            this.traceStore.state.payload.logging_from = this.store.state.loggedAtFrom

            this.store.state.waiting = true

            setTimeout(() => {
              this.store.state.waiting = false

              this.update()
            }, 1000)
          })
    },
    onGraphClick(mouseEvent: MouseEvent) {
      // @ts-ignore TODO
      const chart = this.$refs.tracesGraphRef[0].chart

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
