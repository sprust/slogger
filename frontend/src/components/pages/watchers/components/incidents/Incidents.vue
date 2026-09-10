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
      :expand-row-keys="incidentsStore.expandedIncidentIds"
      @expand-change="rememberExpanded"
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
import {useWatcherIncidentStatStore} from "../../../../../store/watcherIncidentStatStore.ts";

const IncidentEvents = defineAsyncComponent(() => import("./IncidentEvents.vue"))

export default defineComponent({
  components: {IncidentEvents},

  data() {
    return {
      closing: {} as { [id: string]: boolean },
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
      // The count beside the list and the badge in the header come from the same read,
      // so asking for the list is what puts them right too.
      this.statStore.findStat()

      return this.incidentsStore.find()
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
    watcherName(watcherId: number): string {
      return this.watchersStore.items.find(watcher => watcher.id === watcherId)?.name
          ?? `Watcher #${watcherId}`
    },
    watcherTypeTitle(watcherId: number): string {
      const type = this.watchersStore.items.find(watcher => watcher.id === watcherId)?.type

      return type ? this.watcherTypesStore.titleOf(type) : ''
    },
    /** el-table hands over every open row, so the list is taken from it rather than kept in step by hand. */
    rememberExpanded(_row: WatcherIncident, expanded: Array<WatcherIncident>) {
      this.incidentsStore.expandedIncidentIds = expanded.map(incident => incident.id)
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

    // Once, not on every visit. Coming back to the tab is not a reason to throw away
    // the list the reader left — the Refresh button beside the filters is, and it is the
    // only thing that should collapse the rows they had opened.
    if (!this.incidentsStore.loaded) {
      this.update()
    }
  },
})
</script>

<style scoped>
</style>
