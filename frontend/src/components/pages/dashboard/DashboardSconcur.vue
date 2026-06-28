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
import {useDashboardSconcurStore} from "./store/dashboardSconcurStore.ts";
import {Loading as IconLoading, Refresh as IconRefresh} from "@element-plus/icons-vue";

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, Filler);

const WINDOW_MS = 300_000; // rolling window: the last 5 minutes
const COLORS = ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399', '#9B59B6'];

interface MetricDef {
  key: string
  label: string
}

interface HistoryPoint {
  label: string

  [key: string]: string | number
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
      metrics: [
        {key: 'requests_in_flight', label: 'In-flight requests'},
        {key: 'rps', label: 'RPS (requests/sec)'},
        {key: 'cpu_percent', label: 'CPU, %'},
        {key: 'memory_rss_mb', label: 'Memory RSS, MB'},
        {key: 'goroutines', label: 'Goroutines'},
        {key: 'requests_avg_ms', label: 'Avg duration, ms'},
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
    chartData() {
      return {
        labels: this.history.map(p => p.label),
        datasets: this.selectedMetrics.map((key, index) => {
          const metric = this.metrics.find(m => m.key === key)
          const color = COLORS[index % COLORS.length]

          return {
            label: metric?.label ?? key,
            data: this.history.map(p => p[key] as number),
            borderColor: color,
            backgroundColor: color,
            fill: false,
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

      let rps = 0

      if (this.prevCompleted !== null) {
        rps = Math.max(0, stat.requests_completed - this.prevCompleted)
      }

      this.prevCompleted = stat.requests_completed

      const now = Date.now()

      this.history.push({
        ts: now,
        label: new Date(now).toLocaleTimeString(),
        requests_in_flight: stat.requests_in_flight,
        rps: rps,
        cpu_percent: Math.round(stat.cpu_percent * 10) / 10,
        memory_rss_mb: Math.round(stat.memory_rss_bytes / 1048576),
        goroutines: stat.goroutines,
        requests_avg_ms: Math.round(stat.requests_avg_ms * 100) / 100,
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
          <el-statistic title="In-flight" :value="store.stat.requests_in_flight"/>
        </el-col>
        <el-col :span="4">
          <el-statistic title="Completed" :value="store.stat.requests_completed"/>
        </el-col>
        <el-col :span="3">
          <el-statistic title="CPU, %" :value="store.stat.cpu_percent" :precision="1"/>
        </el-col>
        <el-col :span="4">
          <el-statistic title="RSS, MB" :value="rssMb(store.stat.memory_rss_bytes)"/>
        </el-col>
        <el-col :span="4">
          <el-statistic title="Avg, ms" :value="store.stat.requests_avg_ms" :precision="2"/>
        </el-col>
      </el-row>

      <el-divider/>

      <el-text size="small" tag="b">Workers ({{ store.stat.workers.length }})</el-text>
      <el-table
          :data="store.stat.workers"
          size="small"
          border
          style="width: 100%; margin: 8px 0 14px"
      >
        <el-table-column prop="pid" label="PID" width="90"/>
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
        <el-table-column prop="requests_in_flight" label="In-flight"/>
        <el-table-column prop="requests_completed" label="Completed"/>
        <el-table-column label="Avg, ms">
          <template #default="{ row }">{{ row.requests_avg_ms.toFixed(2) }}</template>
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
