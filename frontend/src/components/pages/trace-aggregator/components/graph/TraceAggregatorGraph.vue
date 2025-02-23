<template>
  <div v-for="graph in traceAggregatorGraphStore.graphs">
    <el-row>
      {{ graph.name }}
    </el-row>
    <Bar
        ref="tracesGraphRef"
        :style="`min-height: ${graphItemHeight}; max-height: ${graphItemHeight}`"
        :data="graph.data"
        :options="traceAggregatorGraphStore.graphOptions"
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

import {useTraceAggregatorGraphStore} from "./store/traceAggregatorGraphStore.ts";
import {useTraceAggregatorTimestampPeriodStore} from "./store/traceAggregatorTimestampPeriodsStore.ts";
import {useTraceAggregatorStore} from "../traces/store/traceAggregatorStore.ts";
import {convertDateStringToLocalFull} from "../../../../../utils/helpers.ts";
import {useTraceAggregatorTimestampFieldsStore} from "./store/traceAggregatorTimestampFieldsStore.ts";

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

export default defineComponent({
  components: {
    Bar,
  },

  data() {
    return {
      traceStore: useTraceAggregatorStore(),
    }
  },

  computed: {
    traceAggregatorGraphStore() {
      return useTraceAggregatorGraphStore()
    },
    traceAggregatorTimestampFieldsStore() {
      return useTraceAggregatorTimestampFieldsStore()
    },
    traceAggregatorTimestampPeriodStore() {
      return useTraceAggregatorTimestampPeriodStore()
    },
    graphItemHeight(): string {
      if (this.traceAggregatorGraphStore.graphs.length <= 1) {
        return '60vh'
      }

      return (80 / this.traceAggregatorGraphStore.graphs.length) + 'vh'
    }
  },

  methods: {
    update() {
      if (this.traceAggregatorGraphStore.waiting || !this.traceAggregatorGraphStore.playGraph) {
        return
      }

      this.traceStore.dispatch('prepareCommonPayloadData')

      // @ts-ignore TODO
      this.traceAggregatorGraphStore.payload.timestamp_period = this.traceAggregatorTimestampPeriodStore.selectedTimestampPeriod
      // @ts-ignore TODO
      this.traceAggregatorGraphStore.payload.timestamp_step = this.traceAggregatorTimestampPeriodStore.selectedTimestampStep

      this.traceAggregatorGraphStore.payload.service_ids = this.traceStore.state.payload.service_ids
      this.traceAggregatorGraphStore.payload.logging_to = this.traceStore.state.payload.logging_to
          ? this.traceStore.state.payload.logging_to
          : undefined
      this.traceAggregatorGraphStore.payload.types = this.traceStore.state.payload.types
      this.traceAggregatorGraphStore.payload.tags = this.traceStore.state.payload.tags
      this.traceAggregatorGraphStore.payload.statuses = this.traceStore.state.payload.statuses
      this.traceAggregatorGraphStore.payload.duration_from = this.traceStore.state.payload.duration_from
      this.traceAggregatorGraphStore.payload.duration_to = this.traceStore.state.payload.duration_to
      this.traceAggregatorGraphStore.payload.memory_from = this.traceStore.state.payload.memory_from
      this.traceAggregatorGraphStore.payload.memory_to = this.traceStore.state.payload.memory_to
      this.traceAggregatorGraphStore.payload.cpu_from = this.traceStore.state.payload.cpu_from
      this.traceAggregatorGraphStore.payload.cpu_to = this.traceStore.state.payload.cpu_to
      this.traceAggregatorGraphStore.payload.data = this.traceStore.state.payload.data
      this.traceAggregatorGraphStore.payload.has_profiling = this.traceStore.state.payload.has_profiling

      this.traceAggregatorGraphStore
          .findMetrics(
              // @ts-ignore TODO
              this.traceAggregatorTimestampFieldsStore.selectedFields,
              this.traceStore.state.customFields,
          )
          .finally(() => {
            if (!this.traceAggregatorGraphStore.playGraph) {
              return
            }

            this.traceStore.state.payload.logging_from = this.traceAggregatorGraphStore.loggedAtFrom

            this.traceAggregatorGraphStore.waiting = true

            setTimeout(() => {
              this.traceAggregatorGraphStore.waiting = false

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

      this.traceStore.state.startOfDay = false

      this.traceStore.state.payload.logging_from = new Date(
          convertDateStringToLocalFull(this.traceAggregatorGraphStore.metrics[index].timestamp)
      )
      this.traceStore.state.payload.logging_to = new Date(
          convertDateStringToLocalFull(this.traceAggregatorGraphStore.metrics[index].timestamp_to)
      )

      this.traceAggregatorGraphStore.showGraph = false
    }
  },
  watch: {
    'traceAggregatorGraphStore.playGraph'() {
      if (this.traceAggregatorGraphStore.playGraph) {
        this.update()
      }
    }
  },
})
</script>

<style scoped>
</style>
