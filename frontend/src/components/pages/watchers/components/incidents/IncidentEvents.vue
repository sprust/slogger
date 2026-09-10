<template>
  <div v-loading="loading" style="padding: 0 20px 10px 20px">
    <!-- An incident outlives the watcher that found it, and reading its events takes that
         watcher's type: which numbers an event holds is the type's, and the event does not
         carry it. Deleting a watcher from the panel is soft and leaves the row, so this is
         a watcher whose row is gone altogether. -->
    <el-text v-if="watcherMissing" type="info">
      The watcher behind this incident is gone, so its events cannot be read.
    </el-text>

    <el-text v-else-if="!loading && events.length === 0" type="info">
      No events.
    </el-text>

    <el-table
        v-else-if="events.length > 0"
        :data="events"
        :border="true"
        row-key="id"
        :expand-row-keys="expandedEventIds"
        @expand-change="rememberExpanded"
    >
      <el-table-column type="expand">
        <template #default="props">
          <div style="padding: 0 20px 10px 20px">
            <el-text v-if="!props.row.payload" type="info">
              This event was written in a shape this version cannot read.
            </el-text>
            <el-text v-else-if="groupsOf(props.row).length === 0" type="info">
              This watcher reads counters, not traces, so there is nothing to show here.
            </el-text>
            <el-table
                v-else
                :data="groupsOf(props.row)"
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
              <el-table-column label="Slowest, sec" width="110">
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
import {
  BufferOverflowEvent,
  InvalidBufferGrownEvent,
  NoNewTracesEvent,
  SlowTracesEvent,
  ManyTracesEvent,
  useIncidentsStore,
  WatcherIncident,
  WatcherIncidentEvent,
  WatcherIncidentEventGroup,
} from "../../store/incidentsStore.ts";
import {useWatchersStore} from "../../store/watchersStore.ts";
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
 * A column of one watcher type's payload, named against the type the server answers with.
 *
 * The key is checked at build time: a number renamed on the server and regenerated into
 * the schema fails here rather than showing an empty column nobody notices.
 */
function setting<E extends WatcherIncidentEvent>(
    key: keyof NonNullable<E['payload']>['settings'] & string,
    title: string,
): PayloadColumn {
  return {bucket: 'settings', key, title}
}

function measured<E extends WatcherIncidentEvent>(
    key: keyof NonNullable<E['payload']>['measured'] & string,
    title: string,
): PayloadColumn {
  return {bucket: 'measured', key, title}
}

/**
 * The columns each watcher type's events fill, in the order the server writes them.
 *
 * One list per type rather than one list probed for non-null values: an incident's events
 * all come from the same watcher, so the columns are known before the first row is read.
 */
const columnsByType: Record<string, Array<PayloadColumn>> = {
  bufferOverflow: [
    setting<BufferOverflowEvent>('threshold', 'Limit'),
    measured<BufferOverflowEvent>('buffer_count', 'Traces in the buffer'),
  ],
  invalidBufferGrown: [
    setting<InvalidBufferGrownEvent>('threshold', 'Limit'),
    measured<InvalidBufferGrownEvent>('invalid_count', 'Broken traces'),
    measured<InvalidBufferGrownEvent>('since', 'Counted since'),
  ],
  noNewTraces: [
    setting<NoNewTracesEvent>('period_minutes', 'Minutes without traces'),
    measured<NoNewTracesEvent>('window_from', 'From'),
    measured<NoNewTracesEvent>('window_to', 'To'),
  ],
  manyTraces: [
    setting<ManyTracesEvent>('window_minutes', 'Minutes counted'),
    setting<ManyTracesEvent>('threshold', 'More traces than'),
    measured<ManyTracesEvent>('window_count', 'Traces counted'),
  ],
  slowTraces: [
    setting<SlowTracesEvent>('duration', 'Longer than, sec'),
    setting<SlowTracesEvent>('window_minutes', 'Minutes counted'),
    measured<SlowTracesEvent>('slowest', 'Slowest trace, sec'),
  ],
}

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
    watchersStore() {
      return useWatchersStore()
    },
    /** Empty for a watcher this panel is older than: no endpoint to ask, no columns to show. */
    watcherType(): string {
      return this.watchersStore.items.find(watcher => watcher.id === this.incident.watcher_id)?.type ?? ''
    },
    events(): Array<WatcherIncidentEvent> {
      return this.incidentsStore.events[this.incident.id] ?? []
    },
    loading(): boolean {
      // The watchers too: which endpoint answers follows the type, so there is nothing to
      // ask for until that list is in hand.
      return this.incidentsStore.loadingEvents[this.incident.id] === true
          || (!this.watchersStore.loaded && this.watchersStore.loading)
    },
    /** Told apart from a list that has simply not arrived yet, which would say the same. */
    watcherMissing(): boolean {
      return this.watchersStore.loaded && !this.watcherType
    },
    exhausted(): boolean {
      return this.incidentsStore.eventsExhausted[this.incident.id] === true
    },
    /** The columns of this incident's watcher. Every event under it comes from the same one. */
    columns(): Array<PayloadColumn> {
      return columnsByType[this.watcherType] ?? []
    },
  },

  methods: {
    /** Empty for an event stored under a shape the server can no longer read. */
    payloadValue(event: WatcherIncidentEvent, column: PayloadColumn): unknown {
      const payload = event.payload

      return payload ? (payload[column.bucket] as Record<string, unknown>)[column.key] : null
    },
    /** Only the watchers that are about traces carry these; the ones that read counters have no groups at all. */
    groupsOf(event: WatcherIncidentEvent): Array<WatcherIncidentEventGroup> {
      const payload = event.payload

      return payload && 'groups' in payload ? payload.groups : []
    },
    loadMore() {
      this.incidentsStore.findMoreEvents(this.incident.id, this.watcherType)
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

  watch: {
    /**
     * The events are asked for once the watcher behind the incident is known, not on
     * mount: which endpoint answers follows the type, and a row restored open on page
     * load renders before the watchers list has arrived.
     */
    watcherType: {
      immediate: true,
      handler(type: string) {
        if (type && !this.incidentsStore.events[this.incident.id]) {
          this.incidentsStore.findEvents(this.incident.id, type)
        }
      },
    },
  },

  mounted() {
    if (this.servicesStore.items.length === 0 && !this.servicesStore.loading) {
      this.servicesStore.findServices()
    }
  },
})
</script>

<style scoped>
</style>
