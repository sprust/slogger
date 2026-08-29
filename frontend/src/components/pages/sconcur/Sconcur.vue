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
import {
  groupKey,
  MASTER_SOURCE,
  type StatRow,
  useSconcurStore,
  WINDOW_MS,
  workerKey,
} from "./store/sconcurStore.ts";
import {Loading as IconLoading, Refresh as IconRefresh} from "@element-plus/icons-vue";

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, Filler);

const DASH = '—'; // what a section the pool does not report reads as
const COLORS = ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399', '#9B59B6'];

interface MetricDef {
  key: string
  label: string
}

interface SourceDef {
  key: string
  label: string
}

export default defineComponent({
  components: {
    Line,
  },

  data() {
    return {
      metrics: [
        {key: 'requests_in_flight', label: 'In-flight requests'},
        {key: 'rps', label: 'RPS (requests/sec)'},
        {key: 'cpu_percent', label: 'CPU, %'},
        {key: 'memory_rss_mb', label: 'Memory RSS, MB'},
        {key: 'goroutines', label: 'Goroutines'},
        {key: 'requests_avg_ms', label: 'Avg duration, ms'},
        {key: 'consumers_in_flight', label: 'In-flight handling'},
        {key: 'consumers_rate', label: 'Handled/sec'},
        {key: 'consumers_avg_ms', label: 'Handled avg duration, ms'},
      ] as MetricDef[],
    }
  },

  computed: {
    store() {
      return useSconcurStore()
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
    /** How long the chart's window is, said in the header the column shares with it. */
    windowLabel(): string {
      return `${Math.round(WINDOW_MS / 60_000)}m`
    },
    hasConsumers(): boolean {
      return this.store.stat?.workers.some(worker => worker.consumers) ?? false
    },
    /**
     * What the chart can be pointed at: the master as a whole, one of its pools, or one
     * worker. Built from the current snapshot, so a worker the master replaced disappears
     * from the list as soon as it is gone.
     */
    sources(): SourceDef[] {
      const stat = this.store.stat

      const list: SourceDef[] = [{key: MASTER_SOURCE, label: 'Master (totals)'}]

      if (!stat) {
        return list
      }

      for (const group of stat.groups) {
        list.push({key: groupKey(group.name), label: `Group: ${group.name}`})
      }

      for (const worker of stat.workers) {
        list.push({key: workerKey(worker.pid), label: `Worker ${worker.pid} · ${worker.group}`})
      }

      return list
    },
    /**
     * The source actually charted. A worker that went away falls back to the master
     * rather than leaving the chart empty with no explanation.
     */
    chartedSource(): string {
      return this.sources.some(source => source.key === this.store.selectedSource)
        ? this.store.selectedSource
        : MASTER_SOURCE
    },
    chartData() {
      const source = this.chartedSource

      return {
        labels: this.store.history.map(p => p.label),
        datasets: this.store.selectedMetrics.map((key, index) => {
          const metric = this.metrics.find(m => m.key === key)
          const color = COLORS[index % COLORS.length]

          return {
            label: metric?.label ?? key,
            data: this.store.history.map(p => p.values[source]?.[key] ?? null),
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
    /**
     * The average of one metric of one source over the samples held right now — the same
     * window the chart draws, so a number in the table and the line above it agree.
     * Null when the source reports nothing for it.
     */
    windowAverage(sourceKey: string, metricKey: string): number | null {
      let sum = 0
      let count = 0

      for (const sample of this.store.history) {
        const value = sample.values[sourceKey]?.[metricKey]

        if (typeof value === 'number') {
          sum += value
          count++
        }
      }

      return count === 0 ? null : sum / count
    },

    rssMb(bytes: number): number {
      return Math.round(bytes / 1048576)
    },

    /**
     * A row reports one of the two sections, never both: a server pool counts requests,
     * a consumer pool counts queue messages. The missing one is absent rather than zero —
     * the panel omits it — so it reads as a dash instead of a count nobody keeps.
     *
     * The two are kept in columns of their own rather than merged into one. Merged, the
     * column read as "work done" but summed to something no card above it shows: requests
     * and messages added together. Split, every count column adds up to the card that
     * carries its name.
     */
    requestsInFlight(row: StatRow): number | string {
      return row.requests?.in_flight ?? DASH
    },

    completed(row: StatRow): number | string {
      return row.requests?.completed ?? DASH
    },

    handling(row: StatRow): number | string {
      return row.consumers?.in_flight ?? DASH
    },

    handled(row: StatRow): number | string {
      return row.consumers?.acked ?? DASH
    },

    avgMs(row: StatRow): string {
      const value = row.consumers?.avg_ms ?? row.requests?.avg_ms

      return value === undefined ? DASH : value.toFixed(2)
    },

    /**
     * The same average over the chart's window rather than at this instant.
     *
     * The panel's own avg_ms is cumulative since the worker started, so a pool that was
     * busy an hour ago keeps reporting that hour. This is what the last fifteen minutes
     * actually looked like, which is the question the chart beside it answers.
     */
    avgMsOverWindow(row: StatRow): string {
      const metric = row.consumers ? 'consumers_avg_ms' : (row.requests ? 'requests_avg_ms' : null)

      if (metric === null) {
        return DASH
      }

      const average = this.windowAverage(this.rowKey(row), metric)

      return average === null ? DASH : average.toFixed(2)
    },

    /** Which sampled source a table row is. Workers carry a pid; groups carry a name. */
    rowKey(row: StatRow): string {
      return 'pid' in row ? workerKey(row.pid) : groupKey((row as { name: string }).name)
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
    // A refresh on arrival, so the page is never blank — but the history and the loop are
    // the store's, so what was collected before is still here and a running loop kept
    // running while the page was elsewhere.
    this.store.tick()
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
            @click="store.tick"
        />
        <el-tag v-if="store.stat && store.stat.available" type="success" size="small">
          {{ store.stat.name }}
        </el-tag>
        <el-tag v-else type="info" size="small">unavailable</el-tag>
      </el-space>
      <el-space>
        <el-text size="small">auto-refresh 1s</el-text>
        <el-switch :model-value="store.autoUpdate" @change="store.setAutoUpdate($event as boolean)"/>
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
        <el-col :span="3">
          <el-statistic title="Goroutines" :value="store.stat.goroutines"/>
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
            <el-statistic title="Handling" :value="store.stat.consumers.in_flight"/>
          </el-col>
          <el-col :span="3">
            <!-- acked, the same number the tables below call Handled and the same one
                 the chart's Handled/sec is a rate of. Refused sits beside it, so a queue
                 whose jobs all fail is visible rather than hidden behind a throughput. -->
            <el-statistic title="Handled" :value="store.stat.consumers.acked"/>
          </el-col>
          <el-col :span="3">
            <el-statistic title="Refused" :value="store.stat.consumers.refused"/>
          </el-col>
          <el-col :span="3">
            <el-statistic title="Handled avg, ms" :value="store.stat.consumers.avg_ms" :precision="2"/>
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
          <template #default="{ row }">{{ requestsInFlight(row) }}</template>
        </el-table-column>
        <el-table-column label="Completed" width="110">
          <template #default="{ row }">{{ completed(row) }}</template>
        </el-table-column>
        <el-table-column label="Handling" width="100">
          <template #default="{ row }">{{ handling(row) }}</template>
        </el-table-column>
        <el-table-column label="Handled">
          <template #default="{ row }">{{ handled(row) }}</template>
        </el-table-column>
        <el-table-column label="Refused" width="100">
          <template #default="{ row }">{{ row.consumers ? row.consumers.refused : DASH }}</template>
        </el-table-column>
        <el-table-column label="Avg, ms" width="170">
          <template #header>
            <el-tooltip
                content="Left: cumulative since the worker started, as the panel reports it. Right: the average over the chart's window."
                placement="top"
            >
              <div class="avg-header">
                <div>Avg, ms</div>
                <div class="avg-header-legend">since start / {{ windowLabel }}</div>
              </div>
            </el-tooltip>
          </template>
          <template #default="{ row }">
            {{ avgMs(row) }}
            <span class="avg-window">/ {{ avgMsOverWindow(row) }}</span>
          </template>
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
          <template #default="{ row }">{{ requestsInFlight(row) }}</template>
        </el-table-column>
        <el-table-column label="Completed">
          <template #default="{ row }">{{ completed(row) }}</template>
        </el-table-column>
        <el-table-column v-if="hasConsumers" label="Handling">
          <template #default="{ row }">{{ handling(row) }}</template>
        </el-table-column>
        <el-table-column v-if="hasConsumers" label="Handled">
          <template #default="{ row }">{{ handled(row) }}</template>
        </el-table-column>
        <el-table-column v-if="hasConsumers" label="Refused">
          <template #default="{ row }">{{ row.consumers ? row.consumers.refused : DASH }}</template>
        </el-table-column>
        <el-table-column label="Avg, ms" width="170">
          <template #header>
            <el-tooltip
                content="Left: cumulative since the worker started, as the panel reports it. Right: the average over the chart's window."
                placement="top"
            >
              <div class="avg-header">
                <div>Avg, ms</div>
                <div class="avg-header-legend">since start / {{ windowLabel }}</div>
              </div>
            </el-tooltip>
          </template>
          <template #default="{ row }">
            {{ avgMs(row) }}
            <span class="avg-window">/ {{ avgMsOverWindow(row) }}</span>
          </template>
        </el-table-column>
      </el-table>

      <el-row align="middle" :gutter="12" style="margin-bottom: 10px">
        <el-col :span="7">
          <el-select
              v-model="store.selectedSource"
              filterable
              placeholder="Source"
              style="width: 100%"
          >
            <el-option
                v-for="source in sources"
                :key="source.key"
                :label="source.label"
                :value="source.key"
            />
          </el-select>
        </el-col>
        <el-col :span="10">
          <el-select
              v-model="store.selectedMetrics"
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
        <el-col :span="7">
          <el-text size="small" type="info">
            Last 15 minutes. Every group and worker is sampled, so switching the source
            keeps the history already collected.
          </el-text>
        </el-col>
      </el-row>

      <div style="height: 320px; width: 100%">
        <Line :data="chartData" :options="chartOptions"/>
      </div>
    </template>
  </div>
</template>

<style scoped>
.avg-header {
  line-height: 1.2;
}

.avg-header-legend {
  color: var(--el-text-color-secondary);
  font-weight: normal;
}

.avg-window {
  color: var(--el-text-color-secondary);
  margin-left: 2px;
}
</style>
