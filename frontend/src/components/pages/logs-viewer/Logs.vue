<template>
  <el-row style="height: 6%">
    <el-input
        v-model="logsViewerStore.state.logsParameters.search_query"
        style="width: 300px; height: 35px; margin: 3px"
        placeholder="Search query"
        clearable
    />
    <el-input
        v-model="logsViewerStore.state.logsParameters.level"
        style="width: 300px; height: 35px; margin: 3px"
        placeholder="Level"
        clearable
    />
    <el-button
        :icon="SearchIcon"
        @click="onUpdate"
        :loading="logsViewerStore.state.loading"
        style="height: 35px; margin: 3px"
    />
    <el-pagination
        v-model:current-page="logsViewerStore.state.logsParameters.page"
        layout="prev, pager, next"
        :page-size="logsViewerStore.state.logs.paginator.per_page"
        :total="logsViewerStore.state.logs.paginator.total"
        class="mt-4"
        @current-change="update"
        :disabled="logsViewerStore.state.loading"
        style="height: 35px; margin: 3px"
    />
  </el-row>
  <el-row style="height: 90%; width: 100%">
    <el-scrollbar style="width: 100%">
      <el-table
          :data="logsViewerStore.state.logs.items"
          :border="true"
      >
        <el-table-column type="expand">
          <template #default="props">
            <pre style="text-wrap: wrap;">{{ JSON.parse(props.row.context) }}</pre>
          </template>
        </el-table-column>
        <el-table-column label="Logged at" prop="logged_at">
          <template #default="scope">
            {{ convertDateStringToLocal(scope.row.logged_at, false) }}
          </template>
        </el-table-column>
        <el-table-column label="Message" prop="message"/>
        <el-table-column label="Level" prop="level"/>
      </el-table>
    </el-scrollbar>
  </el-row>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {convertDateStringToLocal} from "../../../utils/helpers.ts";
import {useLogsViewerStoreStore} from "./store/logsViewerStore.ts";
import {Search as SearchIcon} from '@element-plus/icons-vue'

export default defineComponent({
  data() {
    return {
      logsViewerStore: useLogsViewerStoreStore(),
    }
  },
  methods: {
    convertDateStringToLocal,
    update() {
      this.logsViewerStore.dispatch('findLogs')
    },
    onUpdate() {
      this.logsViewerStore.state.logsParameters.page = 1

      this.update()
    },
  },
  computed: {
    SearchIcon() {
      return SearchIcon
    },
  },
  mounted() {
    if (!this.logsViewerStore.state.loading) {
      return
    }

    this.update()
  },
})
</script>

<style>
.flex-grow {
  flex-grow: 1;
}
</style>
