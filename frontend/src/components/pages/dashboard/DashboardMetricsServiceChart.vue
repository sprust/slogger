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
      <Bar v-if="chart.loaded" :data="chartData as any" :options="chartOptions as any"/>
      <div v-else-if="!chart.loading" class="metrics-placeholder">
        <el-text type="info">Not loaded. Press Refresh.</el-text>
      </div>
    </div>
  </el-card>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {
  ActiveElement,
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  ChartEvent,
  Legend,
  LinearScale,
  Title,
  Tooltip,
  TooltipItem
} from 'chart.js'
import {Bar} from 'vue-chartjs'
import {Refresh as IconRefresh} from '@element-plus/icons-vue'
import {
  DashboardServiceMetrics,
  DashboardTraceMetric,
  metricSlotMinutes,
  useDashboardMetricsStore
} from "./store/dashboardMetricsStore.ts";
import {formatUtcDateTime, utcTimestamp} from "../../../utils/helpers.ts";
import {useTraceAggregatorStore} from "../trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import {routes} from "../../../utils/router.ts";
import {TraceAggregatorService} from "../trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

type MetricTotals = {
  logged: number,
  buffered: number,
  stored: number,
}

type MetricType = {
  name: string,
  stored: number,
}

const slotMs = metricSlotMinutes * 60 * 1000

function slotOf(timestamp: string): string {
  const at = utcTimestamp(timestamp)

  return at === null ? timestamp : formatUtcDateTime(new Date(Math.floor(at / slotMs) * slotMs))
}

const series: Array<{ key: keyof MetricTotals, label: string, color: string }> = [
  {key: 'logged', label: 'logged', color: 'rgba(121,146,248,0.9)'},
  {key: 'buffered', label: 'buffered', color: 'rgb(246,188,2)'},
  {key: 'stored', label: 'stored', color: 'rgba(163,248,121,0.9)'},
]

export default defineComponent({
  components: {Bar},

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
      const stored: Record<string, number> = {}

      this.chart.rows.forEach((row: DashboardTraceMetric) => {
        stored[row.type] = (stored[row.type] ?? 0) + row.stored
      })

      return Object.keys(stored)
          .sort()
          .map(name => ({name, stored: stored[name]}))
    },
    filteredRows(): Array<DashboardTraceMetric> {
      const selected = this.chart.selectedTypes

      if (!selected.length) {
        return this.chart.rows
      }

      return this.chart.rows.filter((row: DashboardTraceMetric) => selected.includes(row.type))
    },
    slotIndexes(): Record<string, number> {
      const indexes: Record<string, number> = {}

      this.chart.slots.forEach((slot: string, index: number) => {
        indexes[slot] = index
      })

      return indexes
    },
    values(): Record<keyof MetricTotals, Array<number>> {
      const values: Record<keyof MetricTotals, Array<number>> = {
        logged: new Array(this.chart.slots.length).fill(0),
        buffered: new Array(this.chart.slots.length).fill(0),
        stored: new Array(this.chart.slots.length).fill(0),
      }

      this.filteredRows.forEach((row: DashboardTraceMetric) => {
        const index = this.slotIndexes[slotOf(row.timestamp)]

        if (index === undefined) {
          return
        }

        values.logged[index] += row.logged
        values.buffered[index] += row.buffered
        values.stored[index] += row.stored
      })

      return values
    },
    totals(): MetricTotals {
      const sum = (items: Array<number>) => items.reduce((total, item) => total + item, 0)

      return {
        logged: sum(this.values.logged),
        buffered: sum(this.values.buffered),
        stored: sum(this.values.stored),
      }
    },
    chartData() {
      return {
        labels: this.chart.slots.map((slot: string) => slot.slice(11, 16)),
        datasets: series.map(item => ({
          label: item.label,
          data: this.values[item.key],
          backgroundColor: item.color,
        })),
      }
    },
    chartOptions() {
      const slots = this.chart.slots

      return {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        interaction: {
          mode: 'index',
          intersect: false,
        },
        plugins: {
          tooltip: {
            callbacks: {
              title: (items: Array<TooltipItem<'bar'>>) => slots[items[0]?.dataIndex ?? 0] ?? '',
              footer: () => 'Click to open in the aggregator',
            },
          },
        },
        onHover: (event: ChartEvent, elements: Array<ActiveElement>) => {
          const target = event.native?.target as HTMLElement | undefined

          if (target) {
            target.style.cursor = elements.length ? 'pointer' : 'default'
          }
        },
        onClick: (_event: ChartEvent, elements: Array<ActiveElement>) => {
          if (elements.length) {
            this.openInAggregator(elements[0].index)
          }
        },
        scales: {
          x: {
            ticks: {
              maxTicksLimit: 24,
            },
            grid: {
              color: 'rgba(121,146,248,0.3)',
            },
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(121,146,248,0.2)',
            },
          },
        },
      }
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
        period: {from, to: from + slotMs},
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
