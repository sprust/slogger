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
            <el-text type="info">
              {{ formatSize(file.size_bytes) }} · {{ file.modified_at }}
            </el-text>
            <el-button
                :icon="IconDownload"
                link
                title="Download"
                @click="store.downloadFile(file)"
            />
          </div>
        </div>
      </div>
    </el-scrollbar>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {Download as IconDownload, Refresh as IconRefresh} from '@element-plus/icons-vue'
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
    selectAll(value: any) {
      this.setSelected(value ? this.store.files.map((file) => file.id) : [])
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
}

.logs-files-list {
  flex: 1;
  min-height: 0;
}

/* Room for the scrollbar, which is drawn over the content. */
:deep(.logs-files-list-view) {
  padding-right: 12px;
}

.logs-files-group {
  margin-bottom: 10px;
}

.logs-file {
  padding-left: 20px;
}

.logs-file-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-left: 24px;
}
</style>
