<template>
  <div class="logs-files">
    <el-space class="logs-files-header">
      <el-checkbox
          :model-value="allSelected"
          :indeterminate="!allSelected && store.selectedFileIds.length > 0"
          :disabled="!store.files.length"
          @change="selectAll"
      >
        All files
      </el-checkbox>
      <el-button
          :icon="IconRefresh"
          :loading="store.filesLoading"
          @click="$emit('refresh')"
      />
    </el-space>

    <el-scrollbar class="logs-files-list" view-class="logs-files-list-view">
      <el-text v-if="store.filesLoaded && !store.files.length" type="info">
        No log files
      </el-text>
      <div v-for="group in groups" :key="group.source" class="logs-files-group">
        <el-checkbox
            :model-value="groupSelected(group)"
            :indeterminate="groupPartlySelected(group)"
            @change="(value: any) => selectGroup(group, !!value)"
        >
          <b>{{ group.source }}</b>
        </el-checkbox>
        <div v-for="file in group.files" :key="file.id" class="logs-file">
          <el-checkbox
              :model-value="isSelected(file)"
              @change="(value: any) => selectFile(file, !!value)"
          >
            <span :title="file.folder + '/' + file.name">{{ file.name }}</span>
          </el-checkbox>
          <div class="logs-file-info">
            <el-text type="info" class="logs-file-meta">
              {{ formatSize(file.size_bytes) }} · {{ file.modified_at }}
            </el-text>
            <span class="logs-file-actions">
              <el-button
                  :icon="IconDownload"
                  link
                  title="Download"
                  @click="store.downloadFile(file)"
              />
              <!-- Laid out for every file, so the rows stay the same whether a file can go or not. -->
              <el-popconfirm
                  title="Delete this file?"
                  confirm-button-text="Delete"
                  cancel-button-text="Cancel"
                  @confirm="onDelete(file)"
              >
                <template #reference>
                  <el-button
                      :icon="IconDelete"
                      :class="{'logs-file-hidden': !file.can_delete}"
                      :disabled="!file.can_delete"
                      link
                      type="danger"
                      title="Delete"
                  />
                </template>
              </el-popconfirm>
            </span>
          </div>
        </div>
      </div>
    </el-scrollbar>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {Delete as IconDelete, Download as IconDownload, Refresh as IconRefresh} from '@element-plus/icons-vue'
import {LogFile, useLogsViewerStore} from "../store/logsViewerStore.ts";

type FilesGroup = {
  source: string,
  files: Array<LogFile>,
}

export default defineComponent({
  emits: ['change', 'refresh'],

  computed: {
    store() {
      return useLogsViewerStore()
    },
    IconRefresh() {
      return IconRefresh
    },
    IconDownload() {
      return IconDownload
    },
    IconDelete() {
      return IconDelete
    },
    // Sources in the order the backend lists them, the newest file first.
    groups(): Array<FilesGroup> {
      const groups: Array<FilesGroup> = []

      for (const file of this.store.files) {
        let group = groups.find((item) => item.source === file.source)

        if (!group) {
          group = {source: file.source, files: []}

          groups.push(group)
        }

        group.files.push(file)
      }

      for (const group of groups) {
        group.files.sort((a, b) => b.modified_at.localeCompare(a.modified_at))
      }

      return groups
    },
    allSelected(): boolean {
      return this.store.files.length > 0 && this.store.selectedFileIds.length === this.store.files.length
    },
  },

  methods: {
    isSelected(file: LogFile): boolean {
      return this.store.selectedFileIds.includes(file.id)
    },
    groupSelected(group: FilesGroup): boolean {
      return group.files.every((file) => this.isSelected(file))
    },
    groupPartlySelected(group: FilesGroup): boolean {
      return !this.groupSelected(group) && group.files.some((file) => this.isSelected(file))
    },
    setSelected(ids: Array<string>) {
      // Kept in the order of the list, so the same choice always makes the same request.
      const selected = new Set(ids)

      this.store.selectedFileIds = this.store.files
          .map((file) => file.id)
          .filter((id) => selected.has(id))

      this.$emit('change')
    },
    // Everything only from an empty choice; any choice at all is cleared.
    selectAll() {
      this.setSelected(this.store.selectedFileIds.length ? [] : this.store.files.map((file) => file.id))
    },
    selectGroup(group: FilesGroup, value: boolean) {
      const groupIds = group.files.map((file) => file.id)

      const others = this.store.selectedFileIds.filter((id) => !groupIds.includes(id))

      this.setSelected(value ? [...others, ...groupIds] : others)
    },
    selectFile(file: LogFile, value: boolean) {
      const others = this.store.selectedFileIds.filter((id) => id !== file.id)

      this.setSelected(value ? [...others, file.id] : others)
    },
    async onDelete(file: LogFile) {
      const wasSelected = this.isSelected(file)

      if (!await this.store.deleteFile(file)) {
        return
      }

      if (wasSelected) {
        this.$emit('change')
      }
    },
    formatSize(bytes: number): string {
      if (bytes < 1024) {
        return `${bytes} B`
      }

      if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`
      }

      return `${(bytes / 1024 / 1024).toFixed(1)} MB`
    },
  },
})
</script>

<style scoped>
.logs-files {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.logs-files-header {
  flex: none;
  margin-bottom: 5px;
  /* Room for the focus outline of the checkbox. */
  padding: 3px 0 0 3px;
}

.logs-files-list {
  flex: 1;
  min-height: 0;
}

/* Room for the scrollbar, drawn over the content, and for the focus outline. */
:deep(.logs-files-list-view) {
  padding: 3px 12px 0 3px;
}

.logs-files-group {
  margin-bottom: 10px;
}

.logs-file {
  padding-left: 20px;
}

.logs-file-meta {
  white-space: nowrap;
}

.logs-file-actions {
  display: flex;
  align-items: center;
}

.logs-file-hidden {
  visibility: hidden;
}

.logs-file-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-left: 24px;
}
</style>
