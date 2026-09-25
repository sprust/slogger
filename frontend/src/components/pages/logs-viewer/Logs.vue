<template>
  <div class="logs">
    <div class="logs-files-panel">
      <LogsFiles
          @change="onSearch"
          @refresh="onRefresh"
      />
    </div>
    <div class="logs-entries-panel">
      <LogsFilters
          @search="onSearch"
          @continue="store.continueSearch()"
      />
      <el-text v-if="store.filesLoaded && !store.selectedFileIds.length" type="info">
        Choose one or more files on the left.
      </el-text>
      <div class="logs-table-panel">
        <LogsTable
            @newer="store.findEntries(DirectionEnum.Newer, store.page?.newer_cursor ?? null)"
            @older="store.findEntries(DirectionEnum.Older, store.page?.older_cursor ?? null)"
            @start="store.findEntries()"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {LocationQueryValue} from 'vue-router'
import {DirectionEnum} from "../../../api-schema/admin-api-schema.ts";
import {useLogsViewerStore} from "./store/logsViewerStore.ts";
import LogsFiles from "./components/LogsFiles.vue";
import LogsFilters from "./components/LogsFilters.vue";
import LogsTable from "./components/LogsTable.vue";

function queryList(value: LocationQueryValue | LocationQueryValue[] | undefined): Array<string> {
  const values = Array.isArray(value) ? value : [value]

  return values.filter((item): item is string => typeof item === 'string' && item !== '')
}

function queryString(value: LocationQueryValue | LocationQueryValue[] | undefined): string | null {
  return queryList(value)[0] ?? null
}

export default defineComponent({
  name: 'LogsViewer',

  components: {LogsFiles, LogsFilters, LogsTable},

  computed: {
    store() {
      return useLogsViewerStore()
    },
    DirectionEnum() {
      return DirectionEnum
    },
  },

  methods: {
    // The choice lives in the address, so a link opens the same view.
    readQuery(): boolean {
      const query = this.$route.query

      const fileIds = queryList(query.files)

      if (!fileIds.length) {
        return false
      }

      this.store.selectedFileIds = fileIds
      this.store.levels = queryList(query.levels)
      this.store.from = queryString(query.from)
      this.store.to = queryString(query.to)
      this.store.searchQuery = queryString(query.q) ?? ''

      return true
    },
    writeQuery() {
      const query: Record<string, string | Array<string>> = {
        files: this.store.selectedFileIds,
        levels: this.store.levels,
      }

      if (this.store.from) {
        query.from = this.store.from
      }

      if (this.store.to) {
        query.to = this.store.to
      }

      if (this.store.isSearch) {
        query.q = this.store.searchQuery.trim()
      }

      this.$router.replace({query: query})
    },
    onSearch() {
      this.writeQuery()

      this.store.findEntries()
    },
    async onRefresh() {
      await this.store.findFiles()

      this.onSearch()
    },
  },

  async mounted() {
    const fromQuery = this.readQuery()

    await this.store.findFiles()

    if (!fromQuery && !this.store.selectedFileIds.length) {
      this.store.selectDefaultFile()
    }

    this.onSearch()
  },

  beforeUnmount() {
    this.store.stopRetry()
  },
})
</script>

<style scoped>
.logs {
  display: flex;
  height: 100%;
}

.logs-files-panel {
  flex: none;
  width: 300px;
  margin-right: 10px;
}

.logs-entries-panel {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.logs-table-panel {
  flex: 1;
  min-height: 0;
}
</style>
