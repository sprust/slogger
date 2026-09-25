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
import {DirectionEnum} from "../../../api-schema/admin-api-schema.ts";
import {useLogsViewerStore} from "./store/logsViewerStore.ts";
import LogsFiles from "./components/LogsFiles.vue";
import LogsFilters from "./components/LogsFilters.vue";
import LogsTable from "./components/LogsTable.vue";

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
    onSearch() {
      this.store.findEntries()
    },
    async onRefresh() {
      await this.store.findFiles()

      this.onSearch()
    },
  },

  async mounted() {
    await this.store.findFiles()

    if (!this.store.selectedFileIds.length) {
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
  width: 320px;
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
