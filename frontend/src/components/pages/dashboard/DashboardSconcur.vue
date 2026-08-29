<script lang="ts">
import {defineComponent} from "vue";
import {Line} from "vue-chartjs";
import {
  CategoryScale,
  Chart as ChartJS,
  Filler,
  Legend,
  LinearScale,
  LineElement,
  PointElement,
  Title,
  Tooltip,
} from "chart.js";
import {type SconcurStat, useDashboardSconcurStore} from "./store/dashboardSconcurStore.ts";
import {Loading as IconLoading, Refresh as IconRefresh} from "@element-plus/icons-vue";

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, Filler);

const DASH = '—'; // what a section the pool does not report reads as
const WINDOW_MS = 300_000; // rolling window: the last 5 minutes
const COLORS = ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399', '#9B59B6'];

interface MetricDef {
  key: string
  label: string
}

type SconcurStatData = SconcurStat extends null ? never : SconcurStat

/** A group or a worker: both carry the same optional counter sections. */
type StatRow = NonNullable<SconcurStatData>['groups'][number]
  | NonNullable<SconcurStatData>['workers'][number]

interface HistoryPoint {
  label: string

  // null is a gap in the series, not a zero: Chart.js skips such a point with
  // spanGaps false, which is how a section the master does not report reads as absent
  // rather than as an idle one.
  [key: string]: string | number | null
}

export default defineComponent({
  components: {
    Line,
  },

  data() {
    return {
      autoUpdate: false,
      selectedMetrics: ['rps', 'cpu_percent'] as string[],
      history: [] as HistoryPoint[],
      timer: null as number | null,
      prevCompleted: null as number | null,
      prevDelivered: null as number | null,
      // When the previous sample was taken, so a rate can be per elapsed second rather
      // than per poll: samples are not evenly spaced when auto-update is off.
      prevAt: null as number | null,
      metrics: [
        {key: 'requests_in_flight', label: 'In-flight requests'},
        {key: 'rps', label: 'RPS (requests/sec)'},
        {key: 'cpu_percent', label: 'CPU, %'},
        {key: 'memory_rss_mb', label: 'Memory RSS, MB'},
        {key: 'goroutines', label: 'Goroutines'},
        {key: 'requests_avg_ms', label: 'Avg duration, ms'},
        {key: 'consumers_in_flight', label: 'In-flight deliveries'},
        {key: 'consumers_rate', label: 'Deliveries/sec'},
        {key: 'consumers_avg_ms', label: 'Delivery avg duration, ms'},
      ] as MetricDef[],
    }
  },

  computed: {
    store() {
      return useDashboardSconcurStore()
    },
    IconLoading() {
      return IconLoading
    },
    IconRefresh() {
      return IconRefresh
    },
    DASH() {
      return DASH
    },
    hasConsumers(): boolean {
      return this.store.stat?.workers.some(worker => worker.consumers) ?? false
    },
    chartData() {
      return {
        labels: this.history.map(p => p.label),
        datasets: this.selectedMetrics.map((key, index) => {
          const metric = this.metrics.find(m => m.key === key)
          const color = COLORS[index % COLORS.length]

          return {
            label: metric?.label ?? key,
            data: this.history.map(p => p[key] as number | null),
            borderColor: color,
            backgroundColor: color,
            fill: false,
            // A sample the master did not report is a gap, not a zero. Bridging it would
            // draw a flat line at zero for a pool that is absent, which reads exactly like
            // a pool that is idle.
            spanGaps: false,
            tension: 0.3,
            pointRadius: 0,
            borderWidth: 2,
          }
        }),
      }
    },
    chartOptions() {
      return {
        responsive: true,
        maintainAspectRatio: false,
        animation: false as const,
        interaction: {intersect: false, mode: 'index' as const},
        plugins: {
          legend: {display: true},
        },
        scales: {
          y: {beginAtZero: true},
          x: {ticks: {maxTicksLimit: 10, autoSkip: true}},
        },
      }
    },
  },

  methods: {
    async tick() {
      await this.store.findSconcurStat()

      const stat = this.store.stat

      if (!stat || !stat.available) {
        return
      }

      // Both sections are optional: a master serving no requests omits `requests`, one
      // consuming no queue omits `consumers`. Absent is not zero, so nothing is charted
      // for a section that is not there.
      const now = Date.now()

      // Per second, and per *elapsed* second. Samples are not evenly spaced: auto-update
      // is off by default, and a manual refresh after two idle minutes would otherwise
      // plot two minutes of work as one second of it.
      const elapsedSeconds = this.prevAt === null ? 0 : Math.max(0.001, (now - this.prevAt) / 1000)

      const requests = stat.requests
      let rps: number | null = null

      if (requests && this.prevCompleted !== null && elapsedSeconds > 0) {
        rps = Math.max(0, requests.completed - this.prevCompleted) / elapsedSeconds
      }

      this.prevCompleted = requests ? requests.completed : null

      // Only a pool consuming a queue reports this section; an HTTP-only master omits it.
      const consumers = stat.consumers
      let consumersRate: number | null = null

      if (consumers && this.prevDelivered !== null && elapsedSeconds > 0) {
        consumersRate = Math.max(0, consumers.delivered - this.prevDelivered) / elapsedSeconds
      }

      this.prevDelivered = consumers ? consumers.delivered : null
      this.prevAt = now

      this.history.push({
        ts: now,
        label: new Date(now).toLocaleTimeString(),
        requests_in_flight: requests?.in_flight ?? null,
        rps: rps === null ? null : Math.round(rps * 100) / 100,
        cpu_percent: Math.round(stat.cpu_percent * 10) / 10,
        memory_rss_mb: Math.round(stat.memory_rss_bytes / 1048576),
        goroutines: stat.goroutines,
        requests_avg_ms: requests ? Math.round(requests.avg_ms * 100) / 100 : null,
        consumers_in_flight: consumers?.in_flight ?? null,
        consumers_rate: consumersRate === null ? null : Math.round(consumersRate * 100) / 100,
        consumers_avg_ms: consumers ? Math.round(consumers.avg_ms * 100) / 100 : null,
      })

      // keep only the last 5 minutes
      const cutoff = now - WINDOW_MS

      while (this.history.length > 0 && (this.history[0].ts as number) < cutoff) {
        this.history.shift()
      }
    },

    onToggle(value: boolean) {
      this.stopTimer()

      if (value) {
        this.tick()
        this.timer = window.setInterval(() => this.tick(), 1000)
      }
    },

    stopTimer() {
      if (this.timer !== null) {
        clearInterval(this.timer)
        this.timer = null
      }
    },

    rssMb(bytes: number): number {
      return Math.round(bytes / 1048576)
    },

    /**
     * A row reports one of the two sections, never both: a server pool counts requests,
     * a consumer pool counts deliveries. The missing one is absent rather than zero —
     * the panel omits it — so it reads as a dash instead of a count nobody keeps.
     */
    inFlight(row: StatRow): number | string {
      return row.consumers?.in_flight ?? row.requests?.in_flight ?? DASH
    },

    handled(row: StatRow): number | string {
      return row.consumers?.acked ?? row.requests?.completed ?? DASH
    },

    avgMs(row: StatRow): string {
      const value = row.consumers?.avg_ms ?? row.requests?.avg_ms

      return value === undefined ? DASH : value.toFixed(2)
    },

    formatUptime(seconds: number): string {
      const total = Math.round(seconds)

      if (total < 60) {
        return `${total}s`
      }

      const minutes = Math.floor(total / 60)

      if (minutes < 60) {
        return `${minutes}m ${total % 60}s`
      }

      return `${Math.floor(minutes / 60)}h ${minutes % 60}m`
    },
  },

  mounted() {
    this.tick()
  },

  beforeUnmount() {
    this.stopTimer()
  },
})
</script>

<template>
  <div style="width: 100%">
    <el-row align="middle" justify="space-between">
      <el-space>
        <el-text size="large">Sconcur</el-text>
        <el-button
            :loading="store.loading"
            :icon="store.loading ? IconLoading : IconRefresh"
            link
            @click="tick"
        />
        <el-tag v-if="store.stat && store.stat.available" type="success" size="small">
          {{ store.stat.name }}
        </el-tag>
        <el-tag v-else type="info" size="small">unavailable</el-tag>
      </el-space>
      <el-space>
        <el-text size="small">auto-refresh 1s</el-text>
        <el-switch v-model="autoUpdate" @change="onToggle"/>
      </el-space>
    </el-row>

    <el-divider/>

    <el-alert
        v-if="store.stat && !store.stat.available"
        type="info"
        :closable="false"
        title="Sconcur master is not running or the telemetry panel is unreachable"
        description="Start sconcur:servers:master:start with panelPort and adminToken configured."
    />

    <template v-else-if="store.stat">
      <el-row :gutter="12">
        <el-col :span="3">
          <el-statistic title="Workers" :value="store.stat.workers_total"/>
        </el-col>
        <el-col :span="3">
          <el-statistic title="Hung" :value="store.stat.workers_hung"/>
        </el-col>
        <el-col :span="3">
          <el-statistic title="CPU, %" :value="store.stat.cpu_percent" :precision="1"/>
        </el-col>
        <el-col :span="3">
          <el-statistic title="RSS, MB" :value="rssMb(store.stat.memory_rss_bytes)"/>
        </el-col>
        <template v-if="store.stat.requests">
          <el-col :span="3">
            <el-statistic title="In-flight" :value="store.stat.requests.in_flight"/>
          </el-col>
          <el-col :span="3">
            <el-statistic title="Completed" :value="store.stat.requests.completed"/>
          </el-col>
          <el-col :span="3">
            <el-statistic title="Avg, ms" :value="store.stat.requests.avg_ms" :precision="2"/>
          </el-col>
        </template>
        <template v-if="store.stat.consumers">
          <el-col :span="3">
            <el-statistic title="Delivering" :value="store.stat.consumers.in_flight"/>
          </el-col>
          <el-col :span="3">
            <!-- delivered, not acked: the chart series below counts the same thing, and
                 a queue whose jobs all fail would otherwise read "Delivered 0". -->
            <el-statistic title="Delivered" :value="store.stat.consumers.delivered"/>
          </el-col>
          <el-col :span="3">
            <el-statistic title="Delivery avg, ms" :value="store.stat.consumers.avg_ms" :precision="2"/>
          </el-col>
        </template>
      </el-row>

      <el-divider/>

      <el-text size="small" tag="b">Groups ({{ store.stat.groups.length }})</el-text>
      <el-table
          :data="store.stat.groups"
          size="small"
          border
          style="width: 100%; margin: 8px 0 14px"
      >
        <el-table-column prop="name" label="Group" width="140"/>
        <el-table-column label="Workers" width="100">
          <template #default="{ row }">
            {{ row.workers_hung ? `${row.workers_total} (${row.workers_hung} hung)` : row.workers_total }}
          </template>
        </el-table-column>
        <el-table-column label="CPU, %" width="100">
          <template #default="{ row }">{{ row.cpu_percent.toFixed(1) }}</template>
        </el-table-column>
        <el-table-column label="RSS, MB" width="100">
          <template #default="{ row }">{{ rssMb(row.memory_rss_bytes) }}</template>
        </el-table-column>
        <el-table-column prop="goroutines" label="Goroutines" width="110"/>
        <el-table-column label="In-flight" width="100">
          <template #default="{ row }">{{ inFlight(row) }}</template>
        </el-table-column>
        <el-table-column label="Handled">
          <template #default="{ row }">{{ handled(row) }}</template>
        </el-table-column>
        <el-table-column label="Refused" width="100">
          <template #default="{ row }">{{ row.consumers ? row.consumers.refused : DASH }}</template>
        </el-table-column>
        <el-table-column label="Avg, ms" width="100">
          <template #default="{ row }">{{ avgMs(row) }}</template>
        </el-table-column>
      </el-table>

      <el-text size="small" tag="b">Workers ({{ store.stat.workers.length }})</el-text>
      <el-table
          :data="store.stat.workers"
          size="small"
          border
          style="width: 100%; margin: 8px 0 14px"
      >
        <el-table-column prop="pid" label="PID" width="90"/>
        <el-table-column prop="group" label="Group" width="110"/>
        <el-table-column label="Status" width="90">
          <template #default="{ row }">
            <el-tag :type="row.hung ? 'danger' : 'success'" size="small">
              {{ row.hung ? 'hung' : 'ok' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Uptime" width="110">
          <template #default="{ row }">{{ formatUptime(row.uptime_seconds) }}</template>
        </el-table-column>
        <el-table-column label="CPU, %">
          <template #default="{ row }">{{ row.cpu_percent.toFixed(1) }}</template>
        </el-table-column>
        <el-table-column label="RSS, MB">
          <template #default="{ row }">{{ rssMb(row.memory_rss_bytes) }}</template>
        </el-table-column>
        <el-table-column prop="goroutines" label="Goroutines"/>
        <el-table-column label="In-flight">
          <template #default="{ row }">{{ inFlight(row) }}</template>
        </el-table-column>
        <el-table-column label="Handled">
          <template #default="{ row }">{{ handled(row) }}</template>
        </el-table-column>
        <el-table-column v-if="hasConsumers" label="Refused">
          <template #default="{ row }">{{ row.consumers ? row.consumers.refused : DASH }}</template>
        </el-table-column>
        <el-table-column label="Avg, ms">
          <template #default="{ row }">{{ avgMs(row) }}</template>
        </el-table-column>
      </el-table>

      <el-row align="middle" :gutter="12" style="margin-bottom: 10px">
        <el-col :span="10">
          <el-select
              v-model="selectedMetrics"
              multiple
              collapse-tags
              collapse-tags-tooltip
              placeholder="Metrics"
              style="width: 100%"
          >
            <el-option
                v-for="metric in metrics"
                :key="metric.key"
                :label="metric.label"
                :value="metric.key"
            />
          </el-select>
        </el-col>
      </el-row>

      <div style="height: 320px; width: 100%">
        <Line :data="chartData" :options="chartOptions"/>
      </div>
    </template>
  </div>
</template>
