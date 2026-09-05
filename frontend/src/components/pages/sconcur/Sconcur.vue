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
import StatTitle from "./components/StatTitle.vue";
import {Loading as IconLoading, Refresh as IconRefresh} from "@element-plus/icons-vue";

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale, Filler);

const DASH = '—'; // what a pool that counts nothing reads as
const COLORS = ['#409EFF', '#67C23A', '#E6A23C', '#F56C6C', '#909399', '#9B59B6'];

/**
 * What every name on the page means, kept in one place because most of them appear three
 * times — once in the summary, once per table — and an explanation that drifts between
 * the two tables is worse than none.
 *
 * The counter ones are worded without saying whose numbers they are, so the same sentence
 * is true of the master's totals, of one pool and of one worker.
 */
const TIPS = {
  workers: 'Worker processes the master supervises right now, over every group.',
  hung: 'Alive but silent: the master last heard from them over 15 s ago. It catches a jammed worker runtime, not a slow handler — the sender runs beside PHP rather than inside it. The tasks pool is the exception, reporting from PHP itself.',
  cpu: 'CPU as a percentage of one core, so several busy cores put it over 100. Summed over the worker processes it covers; the master\'s own process is not in it.',
  rss: 'Resident memory of the worker processes it covers, the PHP side and the extension together.',
  runtimeTasks: 'Live tasks in the extension runtime of the workers. The tasks pool runs no such runtime and reports zero.',
  inProcess: 'Units of work handed to PHP and not finished yet: requests being served, queue deliveries being handled, task ticks running.',
  finished: 'Units of work that ended, however they ended: requests completed plus deliveries acked or refused. A tick that found nothing to do is not one.',
  refused: 'How many of the finished ones failed — a delivery nacked or rejected, a task tick that threw. A request answered with a 500 is not here: the runtime does not count it as a failure.',
  avgSince: 'How long one unit of work spent in the handler, over everything finished since the workers started.',
  avgSplit: 'How long one unit of work spent in the handler. Left: since the worker started, as the panel reports it. Right: over the chart\'s window, which is empty until something finishes inside it.',
  group: 'The worker pool. The order is the one config/sconcur.php declares the groups in, not the order the panel happens to answer in.',
  groupWorkers: 'Workers of this pool, and how many of them are hung.',
  pid: 'Process id of the worker. A worker the master replaces comes back with a new one.',
  workerGroup: 'The pool this worker belongs to.',
  status: 'ok, or hung when the master last heard from this worker over 15 s ago.',
  uptime: 'Since this worker started serving, not since the master started — a rolling reload puts it back to zero.',
};

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
    StatTitle,
  },

  data() {
    return {
      metrics: [
        {key: 'in_process', label: 'In process'},
        {key: 'finished_rate', label: 'Finished/sec'},
        {key: 'avg_ms', label: 'Avg duration, ms'},
        {key: 'cpu_percent', label: 'CPU, %'},
        {key: 'memory_rss_mb', label: 'Memory RSS, MB'},
        {key: 'runtime_tasks', label: 'Ext tasks'},
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
    tips() {
      return TIPS
    },
    /** How long the chart's window is, said in the header the column shares with it. */
    windowLabel(): string {
      return `${Math.round(WINDOW_MS / 60_000)}m`
    },
    /** The second line of the Avg column's header, naming the two numbers under it. */
    avgLegend(): string {
      return `since start / ${this.windowLabel}`
    },
    /**
     * Whether anything at all counts its work — groups as well as workers, so the two
     * tables and the header cards above them show and hide together. Computed from the
     * workers alone, the Groups table kept permanently dashed columns on a deployment
     * whose pools report nothing.
     */
    hasWork(): boolean {
      const stat = this.store.stat

      if (!stat) {
        return false
      }

      const counts = (row: StatRow) => row.work !== undefined && row.work !== null

      return stat.groups.some(counts) || stat.workers.some(counts)
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
     * The average duration over the samples held right now — the same window the chart
     * draws, so a number in the table and the line above it agree.
     *
     * Taken from the two ends of the window rather than by averaging the samples between
     * them. The panel's avg_ms is cumulative since the worker booted, so the mean of a
     * few hundred of those is just the cumulative figure again — the column would show
     * the same number twice and say nothing. Total time is avg × count at each end; what
     * happened in between is the difference of the two, over the work done in between.
     *
     * The count is `measured` rather than `finished`, because that is what the panel's
     * average is a mean over: a delivery settled without a measured duration adds to the
     * one and not to the other, and dividing by the wrong one would inflate the result.
     *
     * Null when the window holds no measured work: nothing is a truthful answer, and a
     * dash says it.
     */
    windowAverage(sourceKey: string): number | null {
      let first: { avg: number, count: number } | null = null
      let last: { avg: number, count: number } | null = null

      for (const sample of this.store.history) {
        const avg = sample.values[sourceKey]?.avg_ms
        const count = sample.values[sourceKey]?.measured

        if (typeof avg !== 'number' || typeof count !== 'number') {
          continue
        }

        first ??= {avg, count}
        last = {avg, count}
      }

      if (first === null || last === null) {
        return null
      }

      const done = last.count - first.count

      if (done <= 0) {
        return null
      }

      return (last.avg * last.count - first.avg * first.count) / done
    },

    rssMb(bytes: number): number {
      return Math.round(bytes / 1048576)
    },

    /**
     * The one work section every pool reports, whatever it runs.
     *
     * A server pool counts requests and a consumer pool counts queue deliveries — the
     * task pool counts its ticks as deliveries — and the two are the same quantity under
     * two names, so the backend folds them into one section and the table carries one
     * column apiece instead of two, each of them a dash for half the rows. See
     * SconcurWorkObject for why each pair matches.
     *
     * A pool that counts nothing at all has no section, and that reads as a dash: it
     * says "not counted here" rather than "none happened".
     */
    inProcess(row: StatRow): number | string {
      return row.work?.in_process ?? DASH
    },

    finished(row: StatRow): number | string {
      return row.work?.finished ?? DASH
    },

    refused(row: StatRow): number | string {
      return row.work?.refused ?? DASH
    },

    /** Cumulative since the worker started, as the panel reports it. */
    avgMs(row: StatRow): string {
      return row.work === undefined || row.work === null ? DASH : row.work.avg_ms.toFixed(2)
    },

    /** The same average over the chart's window rather than since the worker booted. */
    avgMsOverWindow(row: StatRow): string {
      const average = this.windowAverage(this.rowKey(row))

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

  watch: {
    /**
     * A worker the master replaced leaves the dropdown showing a key nothing answers to,
     * while the chart has already fallen back to the master. Put the two back together.
     */
    sources(list: SourceDef[]) {
      if (!list.some(source => source.key === this.store.selectedSource)) {
        this.store.selectedSource = MASTER_SOURCE
      }
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
          <el-statistic :value="store.stat.workers_total">
            <template #title>
              <StatTitle label="Workers" :tip="tips.workers"/>
            </template>
          </el-statistic>
        </el-col>
        <el-col :span="3">
          <el-statistic :value="store.stat.workers_hung">
            <template #title>
              <StatTitle label="Hung" :tip="tips.hung"/>
            </template>
          </el-statistic>
        </el-col>
        <el-col :span="3">
          <el-statistic :value="store.stat.cpu_percent" :precision="1">
            <template #title>
              <StatTitle label="CPU, %" :tip="tips.cpu"/>
            </template>
          </el-statistic>
        </el-col>
        <el-col :span="3">
          <el-statistic :value="rssMb(store.stat.memory_rss_bytes)">
            <template #title>
              <StatTitle label="RSS, MB" :tip="tips.rss"/>
            </template>
          </el-statistic>
        </el-col>
        <el-col :span="3">
          <el-statistic :value="store.stat.runtime_tasks">
            <template #title>
              <StatTitle label="Ext tasks" :tip="tips.runtimeTasks"/>
            </template>
          </el-statistic>
        </el-col>
        <template v-if="store.stat.work">
          <el-col :span="3">
            <el-statistic :value="store.stat.work.in_process">
              <template #title>
                <StatTitle label="In process" :tip="tips.inProcess"/>
              </template>
            </el-statistic>
          </el-col>
          <el-col :span="3">
            <!-- Everything that ended, whatever came of it — the same number the tables
                 below call Finished and the same one the chart's Finished/sec is a rate
                 of. Refused sits beside it, so a queue whose jobs all fail is visible
                 rather than hidden inside a throughput. -->
            <el-statistic :value="store.stat.work.finished">
              <template #title>
                <StatTitle label="Finished" :tip="tips.finished"/>
              </template>
            </el-statistic>
          </el-col>
          <el-col :span="3">
            <el-statistic :value="store.stat.work.refused">
              <template #title>
                <StatTitle label="Refused" :tip="tips.refused"/>
              </template>
            </el-statistic>
          </el-col>
          <el-col :span="3">
            <el-statistic :value="store.stat.work.avg_ms" :precision="2">
              <template #title>
                <StatTitle label="Avg, ms" :tip="tips.avgSince"/>
              </template>
            </el-statistic>
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
        <el-table-column prop="name" label="Group" width="140">
          <template #header>
            <StatTitle label="Group" :tip="tips.group"/>
          </template>
        </el-table-column>
        <el-table-column label="Workers" width="100">
          <template #header>
            <StatTitle label="Workers" :tip="tips.groupWorkers"/>
          </template>
          <template #default="{ row }">
            {{ row.workers_hung ? `${row.workers_total} (${row.workers_hung} hung)` : row.workers_total }}
          </template>
        </el-table-column>
        <el-table-column label="CPU, %" width="100">
          <template #header>
            <StatTitle label="CPU, %" :tip="tips.cpu"/>
          </template>
          <template #default="{ row }">{{ row.cpu_percent.toFixed(1) }}</template>
        </el-table-column>
        <el-table-column label="RSS, MB" width="100">
          <template #header>
            <StatTitle label="RSS, MB" :tip="tips.rss"/>
          </template>
          <template #default="{ row }">{{ rssMb(row.memory_rss_bytes) }}</template>
        </el-table-column>
        <el-table-column prop="runtime_tasks" label="Ext tasks" width="110">
          <template #header>
            <StatTitle label="Ext tasks" :tip="tips.runtimeTasks"/>
          </template>
        </el-table-column>
        <el-table-column v-if="hasWork" label="In process" width="110">
          <template #header>
            <StatTitle label="In process" :tip="tips.inProcess"/>
          </template>
          <template #default="{ row }">{{ inProcess(row) }}</template>
        </el-table-column>
        <el-table-column v-if="hasWork" label="Finished">
          <template #header>
            <StatTitle label="Finished" :tip="tips.finished"/>
          </template>
          <template #default="{ row }">{{ finished(row) }}</template>
        </el-table-column>
        <el-table-column v-if="hasWork" label="Refused" width="100">
          <template #header>
            <StatTitle label="Refused" :tip="tips.refused"/>
          </template>
          <template #default="{ row }">{{ refused(row) }}</template>
        </el-table-column>
        <el-table-column v-if="hasWork" label="Avg, ms" width="170">
          <template #header>
            <StatTitle label="Avg, ms" :legend="avgLegend" :tip="tips.avgSplit"/>
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
        <el-table-column prop="pid" label="PID" width="90">
          <template #header>
            <StatTitle label="PID" :tip="tips.pid"/>
          </template>
        </el-table-column>
        <el-table-column prop="group" label="Group" width="110">
          <template #header>
            <StatTitle label="Group" :tip="tips.workerGroup"/>
          </template>
        </el-table-column>
        <el-table-column label="Status" width="90">
          <template #header>
            <StatTitle label="Status" :tip="tips.status"/>
          </template>
          <template #default="{ row }">
            <el-tag :type="row.hung ? 'danger' : 'success'" size="small">
              {{ row.hung ? 'hung' : 'ok' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Uptime" width="110">
          <template #header>
            <StatTitle label="Uptime" :tip="tips.uptime"/>
          </template>
          <template #default="{ row }">{{ formatUptime(row.uptime_seconds) }}</template>
        </el-table-column>
        <el-table-column label="CPU, %">
          <template #header>
            <StatTitle label="CPU, %" :tip="tips.cpu"/>
          </template>
          <template #default="{ row }">{{ row.cpu_percent.toFixed(1) }}</template>
        </el-table-column>
        <el-table-column label="RSS, MB">
          <template #header>
            <StatTitle label="RSS, MB" :tip="tips.rss"/>
          </template>
          <template #default="{ row }">{{ rssMb(row.memory_rss_bytes) }}</template>
        </el-table-column>
        <el-table-column prop="runtime_tasks" label="Ext tasks">
          <template #header>
            <StatTitle label="Ext tasks" :tip="tips.runtimeTasks"/>
          </template>
        </el-table-column>
        <el-table-column v-if="hasWork" label="In process">
          <template #header>
            <StatTitle label="In process" :tip="tips.inProcess"/>
          </template>
          <template #default="{ row }">{{ inProcess(row) }}</template>
        </el-table-column>
        <el-table-column v-if="hasWork" label="Finished">
          <template #header>
            <StatTitle label="Finished" :tip="tips.finished"/>
          </template>
          <template #default="{ row }">{{ finished(row) }}</template>
        </el-table-column>
        <el-table-column v-if="hasWork" label="Refused">
          <template #header>
            <StatTitle label="Refused" :tip="tips.refused"/>
          </template>
          <template #default="{ row }">{{ refused(row) }}</template>
        </el-table-column>
        <el-table-column v-if="hasWork" label="Avg, ms" width="170">
          <template #header>
            <StatTitle label="Avg, ms" :legend="avgLegend" :tip="tips.avgSplit"/>
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
.avg-window {
  color: var(--el-text-color-secondary);
  margin-left: 2px;
}
</style>
