<template>
  <div v-loading="loading" style="padding: 0 20px 10px 20px">
    <el-text v-if="!loading && events.length === 0" type="info">
      No events.
    </el-text>

    <el-table
        v-else
        :data="events"
        :border="true"
        row-key="id"
        :expand-row-keys="expandedEventIds"
        @expand-change="rememberExpanded"
    >
      <el-table-column type="expand">
        <template #default="props">
          <div style="padding: 0 20px 10px 20px">
            <el-text v-if="props.row.payload.groups.length === 0" type="info">
              This watcher reads counters, not traces, so there is nothing to show here.
            </el-text>
            <el-table
                v-else
                :data="props.row.payload.groups"
                :border="true"
            >
              <el-table-column label="Service" min-width="120">
                <template #default="scope">
                  {{ serviceName(scope.row.service_id) }}
                </template>
              </el-table-column>
              <el-table-column label="Type" prop="type" min-width="100"/>
              <el-table-column label="Tags" min-width="160">
                <template #default="scope">
                  <el-tag
                      v-for="tag in scope.row.tags"
                      :key="tag"
                      type="info"
                      style="margin-right: 4px"
                  >
                    {{ tag }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column label="Traces" prop="count" width="90"/>
              <el-table-column label="Slowest, s" width="110">
                <template #default="scope">
                  {{ scope.row.duration_max ?? '' }}
                </template>
              </el-table-column>
              <el-table-column label="Slowest trace" min-width="220">
                <template #default="scope">
                  <el-button
                      v-if="scope.row.trace_id"
                      type="info"
                      link
                      @click="openInAggregator(scope.row.trace_id, props.row.occurred_at)"
                  >
                    {{ scope.row.trace_id }}
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="Occurred at" prop="occurred_at" min-width="160"/>
      <!-- A column per field the watcher actually filled in. Every event under one
           incident comes from the same watcher, so the columns are the same all the way
           down and the numbers line up. -->
      <el-table-column
          v-for="column in columns"
          :key="`${column.bucket}.${column.key}`"
          :label="column.title"
          min-width="140"
      >
        <template #default="scope">
          {{ payloadValue(scope.row, column) }}
        </template>
      </el-table-column>
    </el-table>

    <el-button
        v-if="events.length > 0 && !exhausted"
        :loading="loading"
        style="margin-top: 10px"
        @click="loadMore"
    >
      Show more
    </el-button>
  </div>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {useIncidentsStore, WatcherIncident, WatcherIncidentEvent} from "../../store/incidentsStore.ts";
import {
  useTraceAggregatorServicesStore
} from "../../../trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";
import {
  PeriodPresetEnum,
  useTraceAggregatorStore
} from "../../../trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import {
  useTraceAggregatorGraphStore
} from "../../../trace-aggregator/components/graph/store/traceAggregatorGraphStore.ts";
import {formatUtcDateTime, normalizeUtcDateTime, utcTimestamp} from "../../../../../utils/helpers.ts";
import {routes} from "../../../../../utils/router.ts";

/** How far either side of an event the aggregator is opened. */
const periodMargin = 60 * 60 * 1000

type PayloadBucket = 'settings' | 'measured'

type PayloadColumn = {
  bucket: PayloadBucket,
  key: string,
  title: string,
}

/**
 * The payload's fields in the order the server writes them, with the titles their columns
 * get.
 *
 * One list for every watcher type, like the payload itself: an event is read through its
 * incident, and that route names no type. Whichever fields do not apply come back null,
 * and a column nobody filled in is not drawn.
 *
 * `growth_percent` is in both buckets and means two different things: what the watcher
 * was set to react to, and what it saw.
 */
const payloadTitles: Array<PayloadColumn> = [
  {bucket: 'settings', key: 'threshold', title: 'Limit'},
  {bucket: 'measured', key: 'buffer_count', title: 'Traces in the buffer'},
  {bucket: 'measured', key: 'invalid_count', title: 'Broken traces'},
  {bucket: 'measured', key: 'since', title: 'Counted since'},
  {bucket: 'settings', key: 'period_minutes', title: 'Minutes without traces'},
  {bucket: 'measured', key: 'window_from', title: 'From'},
  {bucket: 'measured', key: 'window_to', title: 'To'},
  {bucket: 'settings', key: 'window_minutes', title: 'Minutes counted'},
  {bucket: 'measured', key: 'window_count', title: 'Traces counted'},
  {bucket: 'measured', key: 'window_per_minute', title: 'Traces per minute now'},
  {bucket: 'settings', key: 'baseline_minutes', title: 'Minutes compared with'},
  {bucket: 'measured', key: 'baseline_per_minute', title: 'Traces per minute before'},
  {bucket: 'measured', key: 'growth_percent', title: 'Growth, %'},
  {bucket: 'settings', key: 'growth_percent', title: 'Growth to react to, %'},
  {bucket: 'settings', key: 'duration', title: 'Longer than, s'},
  {bucket: 'measured', key: 'slowest', title: 'Slowest trace, s'},
]

export default defineComponent({
  props: {
    incident: {
      type: Object as PropType<WatcherIncident>,
      required: true,
    },
  },

  computed: {
    incidentsStore() {
      return useIncidentsStore()
    },
    expandedEventIds(): Array<string> {
      return this.incidentsStore.expandedEventIds[this.incident.id] ?? []
    },
    servicesStore() {
      return useTraceAggregatorServicesStore()
    },
    events(): Array<WatcherIncidentEvent> {
      return this.incidentsStore.events[this.incident.id] ?? []
    },
    loading(): boolean {
      return this.incidentsStore.loadingEvents[this.incident.id] === true
    },
    exhausted(): boolean {
      return this.incidentsStore.eventsExhausted[this.incident.id] === true
    },
    /**
     * The columns this incident's events need.
     *
     * The payload has one shape for every watcher type and fills in only what applies, so
     * the columns are worked out from what is there rather than from a list per type.
     */
    columns(): Array<PayloadColumn> {
      return payloadTitles.filter(column => this.events.some(
          (event: WatcherIncidentEvent) => this.payloadValue(event, column) !== null
              && this.payloadValue(event, column) !== undefined
      ))
    },
  },

  methods: {
    payloadValue(event: WatcherIncidentEvent, column: PayloadColumn): unknown {
      return (event.payload[column.bucket] as Record<string, unknown>)[column.key]
    },
    loadMore() {
      this.incidentsStore.findMoreEvents(this.incident.id)
    },
    /** Kept per incident: the events of one are no business of another's row. */
    rememberExpanded(_row: WatcherIncidentEvent, expanded: Array<WatcherIncidentEvent>) {
      this.incidentsStore.expandedEventIds[this.incident.id] = expanded.map(event => event.id)
    },
    serviceName(serviceId: number): string {
      return this.servicesStore.items.find(service => service.id === serviceId)?.name
          ?? `Service #${serviceId}`
    },
    /**
     * Opens the aggregator on this one trace.
     *
     * The filter is set from here rather than handed over in the url: the aggregator keeps
     * it in a store that survives navigation and reads no query parameters, so this is
     * what "a link to a trace" means on this panel. The search itself is left to whoever
     * arrives — the filter is a starting point, not a question already asked.
     *
     * `initialized` is set because the aggregator resets its filter the first time it is
     * mounted — without it the trace id would be wiped on arrival.
     */
    openInAggregator(traceId: string, occurredAt: string) {
      const traceAggregatorStore = useTraceAggregatorStore()

      // A live graph rewrites the filter's lower bound every second and would take the
      // window below with it.
      useTraceAggregatorGraphStore().playGraph = false

      traceAggregatorStore.resetFilters()

      traceAggregatorStore.initialized = true
      traceAggregatorStore.payload.trace_id = traceId

      this.applyPeriodAround(traceAggregatorStore, occurredAt)

      this.$router.push(routes.traceAggregator)
    },
    utcBound(at: number): string {
      return normalizeUtcDateTime(formatUtcDateTime(new Date(at))) ?? ''
    },
    /**
     * An hour either side of the moment the watcher spoke.
     *
     * Narrow on purpose: the search reads a period as a range of hourly shards, so a week
     * around one known trace is thousands of collections to index and to scan. `Custom`
     * is what makes the two dates count — any other preset computes `from` itself and
     * throws them away, see PeriodParameters::fromStringValues().
     */
    applyPeriodAround(traceAggregatorStore: ReturnType<typeof useTraceAggregatorStore>, occurredAt: string) {
      const at = utcTimestamp(occurredAt)

      if (at === null) {
        traceAggregatorStore.payload.logging_from_preset = PeriodPresetEnum.LastWeek

        return
      }

      traceAggregatorStore.payload.logging_from_preset = PeriodPresetEnum.Custom
      // Through the plain UTC text the pickers use, not straight from the Date:
      // normalizeUtcDateTime reads a Date's local parts and stamps them as UTC, which
      // would shift a real instant by the browser's offset.
      traceAggregatorStore.payload.logging_from = this.utcBound(at - periodMargin)
      traceAggregatorStore.payload.logging_to = this.utcBound(at + periodMargin)
    },
  },

  mounted() {
    if (!this.incidentsStore.events[this.incident.id]) {
      this.incidentsStore.findEvents(this.incident.id)
    }

    if (this.servicesStore.items.length === 0 && !this.servicesStore.loading) {
      this.servicesStore.findServices()
    }
  },
})
</script>

<style scoped>
</style>
