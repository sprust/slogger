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
                      @click="openInAggregator(scope.row.trace_id)"
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
          :key="column.key"
          :label="column.title"
          min-width="140"
      >
        <template #default="scope">
          {{ scope.row.payload[column.key] }}
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
import {routes} from "../../../../../utils/router.ts";

/**
 * The payload's fields in the order the server writes them, with the titles their columns
 * get.
 *
 * One list for every watcher type, like the payload itself: an event is read through its
 * incident, and that route names no type. Whichever fields do not apply come back null,
 * and a column nobody filled in is not drawn.
 */
const payloadTitles: Array<[keyof WatcherIncidentEvent['payload'], string]> = [
  ['threshold', 'Limit'],
  ['buffer_count', 'Traces in the buffer'],
  ['invalid_count', 'Broken traces'],
  ['since', 'Counted since'],
  ['period_minutes', 'Minutes without traces'],
  ['window_from', 'From'],
  ['window_to', 'To'],
  ['window_minutes', 'Minutes counted'],
  ['window_count', 'Traces counted'],
  ['window_per_minute', 'Traces per minute now'],
  ['baseline_minutes', 'Minutes compared with'],
  ['baseline_per_minute', 'Traces per minute before'],
  ['growth_percent', 'Growth, %'],
  ['threshold_percent', 'Growth to react to, %'],
  ['duration', 'Longer than, s'],
  ['slowest', 'Slowest trace, s'],
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
    columns(): Array<{ key: keyof WatcherIncidentEvent['payload'], title: string }> {
      return payloadTitles
          .filter(([key]) => this.events.some(
              (event: WatcherIncidentEvent) => event.payload[key] !== null
                  && event.payload[key] !== undefined
          ))
          .map(([key, title]) => ({key, title}))
    },
  },

  methods: {
    loadMore() {
      this.incidentsStore.findMoreEvents(this.incident.id)
    },
    serviceName(serviceId: number): string {
      return this.servicesStore.items.find(service => service.id === serviceId)?.name
          ?? `Service #${serviceId}`
    },
    /**
     * Opens the aggregator on this one trace.
     *
     * The filter is filled in and the request made from here rather than handed over in
     * the url: the aggregator keeps its filter in a store that survives navigation and
     * reads no query parameters, so this is what "a link to a trace" means on this panel.
     *
     * `initialized` is set because the aggregator resets its filter the first time it is
     * mounted — without it the trace id would be wiped on arrival.
     */
    openInAggregator(traceId: string) {
      const traceAggregatorStore = useTraceAggregatorStore()

      traceAggregatorStore.resetFilters()

      traceAggregatorStore.initialized = true
      traceAggregatorStore.payload.trace_id = traceId
      // Wide on purpose: an incident is read after the fact, and the default hour would
      // often no longer hold the trace it points at. The preset wins over the dates
      // beside it — see PeriodParameters::fromStringValues().
      traceAggregatorStore.payload.logging_from_preset = PeriodPresetEnum.LastWeek

      traceAggregatorStore.fillTraceAggregator()

      this.$router.push(routes.traceAggregator)
    },
  },

  mounted() {
    if (!this.incidentsStore.events[this.incident.id]) {
      this.incidentsStore.findEvents(this.incident.id)
    }

    if (this.servicesStore.items.length === 0) {
      this.servicesStore.findServices()
    }
  },
})
</script>

<style scoped>
</style>
