<template>
  <IncidentEventsTable
      :events="events"
      :columns="columns"
      :service-names="serviceNames"
      :loading="loading"
      :exhausted="exhausted"
      :watcher-missing="watcherMissing"
      :expanded-event-ids="expandedEventIds"
      @load-more="loadMore"
      @expand-change="rememberExpanded"
      @open-in-aggregator="openInAggregator"
  />
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import IncidentEventsTable from "./IncidentEventsTable.vue";
import {columnsByType, PayloadColumn} from "./incidentEventColumns.ts";
import {
  useIncidentsStore,
  WatcherIncident,
  WatcherIncidentEvent,
  WatcherIncidentEventGroup,
} from "../../store/incidentsStore.ts";
import {useWatchersStore} from "../../store/watchersStore.ts";
import {
  useTraceAggregatorServicesStore
} from "../../../trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";
import {useTraceAggregatorStore} from "../../../trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import {utcTimestamp} from "../../../../../utils/helpers.ts";
import {routes} from "../../../../../utils/router.ts";

/** How far either side of an event the aggregator is opened. */
const periodMargin = 15 * 60 * 1000

export default defineComponent({
  components: {IncidentEventsTable},

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
    serviceNames(): Record<number, string> {
      const names: Record<number, string> = {}

      this.servicesStore.items.forEach(service => {
        names[service.id] = service.name
      })

      return names
    },
  },

  methods: {
    loadMore() {
      this.incidentsStore.findMoreEvents(this.incident.id, this.watcherType)
    },
    /** Kept per incident: the events of one are no business of another's row. */
    rememberExpanded(eventIds: Array<string>) {
      this.incidentsStore.expandedEventIds[this.incident.id] = eventIds
    },
    /**
     * Fills the aggregator's filter with everything this shape knows.
     *
     * The filter is set from here rather than handed over in the url: the aggregator keeps
     * it in a store that survives navigation and reads no query parameters, so this is
     * what "a link to these traces" means on this panel. The search itself is left to
     * whoever arrives, which is what makes filling all of it useful — the id and the
     * shape are two questions, and whoever arrives picks one by clearing the other.
     *
     * The trace id is left in, and it answers on its own: it reduces the search to that
     * trace and the tree it belongs to, whatever else stands beside it. Clearing it is what
     * asks the shape's question instead.
     */
    async openInAggregator(group: WatcherIncidentEventGroup, event: WatcherIncidentEvent) {
      const settings = this.watcherType
          ? await this.watchersStore.findSettings(this.watcherType, this.incident.watcher_id)
          : null

      useTraceAggregatorStore().applyExternalFilter({
        serviceIds: group.service_id ? [group.service_id] : [],
        types: group.type ? [group.type] : [],
        tags: group.tags,
        statuses: settings?.filter?.statuses ?? [],
        traceId: group.trace_id,
        period: this.periodAround(group, event),
      })

      this.$router.push(routes.traceAggregator)
    },
    /**
     * A quarter of an hour either side of the moment the slowest trace started, when the
     * group names it: traces are searched by their start, and a long one started far
     * from the moment the watcher spoke.
     *
     * Without that moment — a counting watcher, or an event stored before groups carried
     * it — the period reaches back over the watcher's window and the longest duration in
     * the group, which is as early as any trace behind the event can have started.
     *
     * Narrow otherwise on purpose: the search reads a period as a range of hourly shards,
     * so a week around one known trace is thousands of collections to index and to scan.
     */
    periodAround(group: WatcherIncidentEventGroup, event: WatcherIncidentEvent): null | { from: number, to: number } {
      const startedAt = utcTimestamp(group.trace_logged_at)
      const at = utcTimestamp(event.occurred_at)

      if (startedAt === null && at === null) {
        return null
      }

      const from = startedAt ?? (at as number) - this.lookBack(group, event)
      const to = startedAt ?? (at as number)

      return {from: from - periodMargin, to: to + periodMargin}
    },
    lookBack(group: WatcherIncidentEventGroup, event: WatcherIncidentEvent): number {
      const settings = event.payload?.settings as Record<string, unknown> | undefined
      const windowMinutes = typeof settings?.window_minutes === 'number' ? settings.window_minutes : 0

      return windowMinutes * 60 * 1000 + (group.duration_max ?? 0) * 1000
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
    if (!this.servicesStore.loaded && !this.servicesStore.loading) {
      this.servicesStore.findServices()
    }
  },
})
</script>

<style scoped>
</style>
