<template>
  <div class="logs-table">
    <el-table
        :data="items"
        :border="true"
        row-key="row_key"
        height="100%"
        v-loading="store.loading"
    >
      <el-table-column type="expand">
        <template #default="props">
          <div class="logs-table-details">
            <el-text v-if="props.row.truncated" type="warning">
              The entry is too long and is cut. Download the file to see all of it.
            </el-text>
            <el-descriptions
                v-if="props.row.type !== 'laravel' && filledFields(props.row).length"
                :column="1"
                :border="true"
                class="logs-table-fields"
            >
              <el-descriptions-item
                  v-for="field in filledFields(props.row)"
                  :key="field.key"
                  :label="field.key"
              >
                {{ field.value }}
              </el-descriptions-item>
            </el-descriptions>
            <pre class="logs-table-text">{{ props.row.text }}</pre>
            <JsonViewer
                v-if="parseContext(props.row) !== undefined"
                :value="parseContext(props.row)"
                :expand-depth="2"
            />
          </div>
        </template>
      </el-table-column>
      <el-table-column label="Time" width="170">
        <template #default="scope">
          {{ scope.row.logged_at }}
        </template>
      </el-table-column>
      <el-table-column label="Level" width="120">
        <template #default="scope">
          <el-tag :type="levelTagType(scope.row.level)">
            {{ levelKeyName(scope.row.level) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column v-if="store.selectedFileIds.length > 1" label="File" width="200">
        <template #default="scope">
          {{ fileName(scope.row.file_id) }}
        </template>
      </el-table-column>
      <el-table-column label="Entry" min-width="300">
        <template #default="scope">
          <span v-if="scope.row.type === 'nginx_access' && field(scope.row, 'status')" class="logs-table-entry">
            {{ field(scope.row, 'method') }} {{ field(scope.row, 'path') }}
            <el-text type="info">
              · {{ field(scope.row, 'status') }} · {{ field(scope.row, 'ip') }} · {{ field(scope.row, 'bytes') ?? 0 }} B
            </el-text>
          </span>
          <span v-else class="logs-table-entry">
            <el-text v-if="field(scope.row, 'env')" type="info">{{ field(scope.row, 'env') }}</el-text>
            {{ scope.row.message }}
          </span>
        </template>
      </el-table-column>
    </el-table>

    <el-space class="logs-table-navigation">
      <el-button
          :disabled="!store.page?.newer_cursor || store.loading"
          @click="$emit('newer')"
      >
        Newer
      </el-button>
      <el-button
          :disabled="!store.page?.older_cursor || store.loading"
          @click="$emit('older')"
      >
        Older
      </el-button>
      <el-button
          :disabled="store.loading"
          @click="$emit('start')"
      >
        Back to start
      </el-button>
    </el-space>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import JsonViewer from "../../../json/JsonViewer.vue";
import {LogEntry, useLogsViewerStore} from "../store/logsViewerStore.ts";
import {levelKeyName, levelTagType} from "../store/logLevels.ts";

type LogEntryRow = LogEntry & { row_key: string }

export default defineComponent({
  components: {JsonViewer},

  emits: ['newer', 'older', 'start'],

  computed: {
    store() {
      return useLogsViewerStore()
    },
    items(): Array<LogEntryRow> {
      return (this.store.page?.items ?? []).map((item) => ({
        ...item,
        row_key: `${item.file_id}:${item.entry_no}`,
      }))
    },
    fileNames(): Map<string, string> {
      return new Map(this.store.files.map((file) => [file.id, file.name]))
    },
  },

  methods: {
    levelKeyName,
    levelTagType,
    field(row: LogEntry, key: string): string | null {
      return row.fields.find((item) => item.key === key)?.value ?? null
    },
    filledFields(row: LogEntry) {
      return row.fields.filter((item) => item.value !== null && item.value !== undefined && item.value !== '')
    },
    fileName(fileId: string): string {
      return this.fileNames.get(fileId) ?? fileId
    },
    parseContext(row: LogEntry): unknown {
      if (!row.context) {
        return undefined
      }

      try {
        return JSON.parse(row.context)
      } catch {
        return undefined
      }
    },
  },
})
</script>

<style scoped>
.logs-table {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.logs-table > .el-table {
  flex: 1;
  min-height: 0;
}

.logs-table-navigation {
  flex: none;
  margin-top: 10px;
}

.logs-table-details {
  padding: 0 10px;
}

.logs-table-fields {
  margin-bottom: 10px;
}

.logs-table-text {
  white-space: pre-wrap;
  word-break: break-all;
}

.logs-table-entry {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-all;
}
</style>
