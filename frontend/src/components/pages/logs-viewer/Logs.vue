<template>
  <el-row style="height: 6%">
    <el-input
        v-model="logsViewerStore.parameters.search_query"
        style="width: 300px; height: 35px; margin: 3px"
        placeholder="Search query"
        clearable
    />
    <el-input
        v-model="logsViewerStore.parameters.level"
        style="width: 300px; height: 35px; margin: 3px"
        placeholder="Level"
        clearable
    />
    <el-button
        :icon="SearchIcon"
        @click="onUpdate"
        :loading="logsViewerStore.loading"
        style="height: 35px; margin: 3px"
    />
    <el-pagination
        v-model:current-page="logsViewerStore.parameters.page"
        layout="prev, pager, next"
        :page-size="logsViewerStore.logs.paginator.per_page"
        :total="logsViewerStore.logs.paginator.total"
        class="mt-4"
        @current-change="update"
        :disabled="logsViewerStore.loading"
        style="height: 35px; margin: 3px"
    />
  </el-row>
  <el-row style="height: 90%; width: 100%">
    <el-scrollbar style="width: 100%">
      <el-table
          :data="logsViewerStore.logs.items"
          :border="true"
      >
        <el-table-column type="expand">
          <template #default="props">
            <pre style="text-wrap: wrap;">{{ JSON.parse(props.row.context) }}</pre>
          </template>
        </el-table-column>
        <el-table-column label="Logged at" prop="logged_at">
          <template #default="scope">
            {{ scope.row.logged_at }}
          </template>
        </el-table-column>
        <el-table-column label="Message" prop="message" min-width="200">
          <template #default="scope">
            <el-tooltip
                :content="scope.row.message"
                placement="top"
                :show-after="300"
                :disabled="scope.row.message.length <= 120"
            >
              <span class="message-cell">{{ scope.row.message }}</span>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column label="Level" prop="level"/>
      </el-table>
    </el-scrollbar>
  </el-row>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {Search as SearchIcon} from '@element-plus/icons-vue'
import {useLogsViewerStore} from "./store/logsViewerStore.ts";

export default defineComponent({
  name: 'LogsViewer',

  computed: {
    logsViewerStore() {
      return useLogsViewerStore()
    },
    SearchIcon() {
      return SearchIcon
    },
  },

  methods: {
    update() {
      this.logsViewerStore.findLogs()
    },
    onUpdate() {
      this.logsViewerStore.parameters.page = 1

      this.update()
    },
  },
  mounted() {
    if (!this.logsViewerStore.loading) {
      return
    }

    this.update()
  },
})
</script>

<style scoped>
.message-cell {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-all;
  cursor: default;
}
</style>
