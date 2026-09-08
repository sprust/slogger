<template>
  <el-space wrap style="margin-bottom: 10px">
    <el-select
        v-model="watcherId"
        placeholder="Any watcher"
        style="width: 260px"
        clearable
        filterable
        @change="applyFilter"
    >
      <el-option
          v-for="watcher in watchersStore.items"
          :key="watcher.id"
          :label="watcher.name"
          :value="watcher.id"
      />
    </el-select>
    <el-select
        v-model="status"
        placeholder="Any status"
        style="width: 160px"
        clearable
        @change="applyFilter"
    >
      <el-option label="Opened" :value="statusOpened"/>
      <el-option label="Closed" :value="statusClosed"/>
    </el-select>
    <el-button
        :icon="IconRefresh"
        :loading="incidentsStore.loading"
        @click="update"
    />
    <el-text type="info">
      Opened: {{ statStore.openedCount }}
    </el-text>
  </el-space>

  <el-table
      :data="incidentsStore.items"
      :border="true"
      v-loading="incidentsStore.loading"
      row-key="id"
  >
    <el-table-column type="expand">
      <template #default="props">
        <IncidentEvents :incident="props.row"/>
      </template>
    </el-table-column>
    <el-table-column label="Watcher" min-width="180">
      <template #default="scope">
        <el-row>
          {{ watcherName(scope.row.watcher_id) }}
        </el-row>
        <el-row>
          <el-text type="info">
            {{ watcherTypeTitle(scope.row.watcher_id) }}
          </el-text>
        </el-row>
      </template>
    </el-table-column>
    <el-table-column label="Status" width="110">
      <template #default="scope">
        <el-tag :type="scope.row.status === statusOpened ? 'danger' : 'info'">
          {{ scope.row.status }}
        </el-tag>
      </template>
    </el-table-column>
    <el-table-column label="First / last event" min-width="180">
      <template #default="scope">
        {{ scope.row.first_event_at }}
        <br>
        {{ scope.row.last_event_at }}
      </template>
    </el-table-column>
    <el-table-column label="Events" prop="events_count" width="90"/>
    <el-table-column label="Closed at" min-width="160">
      <template #default="scope">
        {{ scope.row.closed_at ?? '' }}
      </template>
    </el-table-column>
    <el-table-column width="100" fixed="right">
      <template #default="scope">
        <el-button
            v-if="scope.row.status === statusOpened"
            type="primary"
            link
            :loading="closing[scope.row.id]"
            @click="close(scope.row)"
        >
          Close
        </el-button>
      </template>
    </el-table-column>
  </el-table>

  <el-space style="margin-top: 10px">
    <el-button
        :disabled="incidentsStore.page === 1 || incidentsStore.loading"
        @click="previousPage"
    >
      Prev
    </el-button>
    <el-text>
      Page {{ incidentsStore.page }}
    </el-text>
    <el-button
        :disabled="!incidentsStore.hasNextPage || incidentsStore.loading"
        @click="nextPage"
    >
      Next
    </el-button>
  </el-space>
</template>

<script lang="ts">
import {defineAsyncComponent, defineComponent} from 'vue'
import {Refresh as IconRefresh} from '@element-plus/icons-vue'
import {useIncidentsStore, WatcherIncident} from "../../store/incidentsStore.ts";
import {WatchersIncidentsListParamsStatusEnum} from "../../../../../api-schema/admin-api-schema.ts";
import {useWatchersStore} from "../../store/watchersStore.ts";
import {useWatcherTypesStore} from "../../store/watcherTypesStore.ts";
import {useWatcherIncidentStatStore, WatcherIncidentFrame} from "../../../../../store/watcherIncidentStatStore.ts";

const IncidentEvents = defineAsyncComponent(() => import("./IncidentEvents.vue"))

/**
 * Dropped when the tab is left, so a list nobody is looking at is not reloaded on every
 * frame. The badge in the header keeps following them either way.
 */
let unsubscribeFrames: null | (() => void) = null

/**
 * The frames of one pass, gathered before anything is re-read.
 *
 * A minute pass that trips five watchers publishes five frames within milliseconds, and
 * each of them makes the same list a page old. Reading it five times over would put five
 * requests in flight for one answer.
 */
let reloadTimeoutId: null | number = null

/** How long to wait for the rest of the pass. One minute pass lands well inside this. */
const reloadDelay = 300

/** Which incidents moved while the wait above was running. */
const pendingIncidentIds = new Set<number>()

export default defineComponent({
  components: {IncidentEvents},

  data() {
    return {
      closing: {} as { [id: number]: boolean },
    }
  },

  computed: {
    incidentsStore() {
      return useIncidentsStore()
    },
    // Read and written straight through to the store rather than copied into data():
    // the store is what the request is built from, and deleting the watcher a filter
    // names clears it there — a local copy would go on showing a watcher that is gone.
    status: {
      get(): WatchersIncidentsListParamsStatusEnum | null {
        return useIncidentsStore().status
      },
      set(value: WatchersIncidentsListParamsStatusEnum | null) {
        useIncidentsStore().status = value
      },
    },
    watcherId: {
      get(): number | null {
        return useIncidentsStore().watcherId
      },
      set(value: number | null) {
        useIncidentsStore().watcherId = value
      },
    },
    watchersStore() {
      return useWatchersStore()
    },
    watcherTypesStore() {
      return useWatcherTypesStore()
    },
    statStore() {
      return useWatcherIncidentStatStore()
    },
    IconRefresh() {
      return IconRefresh
    },
    statusOpened() {
      return WatchersIncidentsListParamsStatusEnum.Opened
    },
    statusClosed() {
      return WatchersIncidentsListParamsStatusEnum.Closed
    },
  },

  methods: {
    update() {
      this.incidentsStore.find()
    },
    applyFilter() {
      this.incidentsStore.applyFilter()
    },
    previousPage() {
      this.incidentsStore.setPage(this.incidentsStore.page - 1)
    },
    nextPage() {
      this.incidentsStore.setPage(this.incidentsStore.page + 1)
    },
    close(incident: WatcherIncident) {
      if (!confirm('Close this incident?')) {
        return
      }

      this.closing[incident.id] = true

      this.incidentsStore.close(incident.id)
          .finally(() => {
            delete this.closing[incident.id]
          })
    },
    /** Gathers the frames of one pass into a single re-read. */
    scheduleReload(incidentId: number) {
      pendingIncidentIds.add(incidentId)

      if (reloadTimeoutId !== null) {
        return
      }

      reloadTimeoutId = window.setTimeout(
        () => {
          reloadTimeoutId = null

          const incidentIds = [...pendingIncidentIds]

          pendingIncidentIds.clear()

          // The events of the incidents that moved, and the list once for all of them.
          incidentIds.forEach(id => {
            if (this.incidentsStore.events[id]) {
              this.incidentsStore.findEvents(id)
            }
          })

          this.incidentsStore.find()
        },
        reloadDelay
      )
    },
    watcherName(watcherId: number): string {
      return this.watchersStore.items.find(watcher => watcher.id === watcherId)?.name
          ?? `Watcher #${watcherId}`
    },
    watcherTypeTitle(watcherId: number): string {
      const type = this.watchersStore.items.find(watcher => watcher.id === watcherId)?.type

      return type ? this.watcherTypesStore.titleOf(type) : ''
    },
  },

  mounted() {
    // The names beside each incident and the titles under them come from these two, and
    // both are read once per session.
    if (!this.watchersStore.loaded) {
      this.watchersStore.find()
    }

    if (!this.watcherTypesStore.loaded) {
      this.watcherTypesStore.find()
    }

    this.update()

    unsubscribeFrames = this.statStore.onFrame((frame: WatcherIncidentFrame) => {
      this.scheduleReload(frame.incident_id)
    })
  },

  unmounted() {
    unsubscribeFrames?.()
    unsubscribeFrames = null

    if (reloadTimeoutId !== null) {
      window.clearTimeout(reloadTimeoutId)
      reloadTimeoutId = null
    }

    pendingIncidentIds.clear()
  },
})
</script>

<style scoped>
</style>
