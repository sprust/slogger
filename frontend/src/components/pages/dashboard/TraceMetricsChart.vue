<template>
  <div class="metrics-chart">
    <Bar :data="chartData as any" :options="chartOptions as any"/>
  </div>
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
import type {DashboardTraceMetric} from "./store/dashboardMetricsStore.ts";
import {filterMetricRows, makeMetricValues, metricSeries, MetricValues} from "./traceMetricsValues.ts";

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

export default defineComponent({
  components: {Bar},

  props: {
    slots: {
      type: Array as PropType<Array<string>>,
      required: true,
    },
    rows: {
      type: Array as PropType<Array<DashboardTraceMetric>>,
      required: true,
    },
    selectedTypes: {
      type: Array as PropType<Array<string>>,
      default: () => [],
    },
    clickHint: {
      type: String,
      default: '',
    },
  },

  emits: ['slot-click'],

  computed: {
    values(): MetricValues {
      return makeMetricValues(this.slots, filterMetricRows(this.rows, this.selectedTypes))
    },
    chartData() {
      return {
        labels: this.slots.map((slot: string) => slot.slice(11, 16)),
        datasets: metricSeries.map(item => ({
          label: item.label,
          data: this.values[item.key],
          backgroundColor: item.color,
        })),
      }
    },
    chartOptions() {
      const slots = this.slots
      const clickHint = this.clickHint

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
              footer: () => clickHint,
            },
          },
        },
        onHover: (event: ChartEvent, elements: Array<ActiveElement>) => {
          const target = event.native?.target as HTMLElement | undefined

          if (target) {
            target.style.cursor = clickHint && elements.length ? 'pointer' : 'default'
          }
        },
        onClick: (_event: ChartEvent, elements: Array<ActiveElement>) => {
          if (clickHint && elements.length) {
            this.$emit('slot-click', elements[0].index)
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
})
</script>

<style scoped>
.metrics-chart {
  position: relative;
  height: 100%;
}
</style>
