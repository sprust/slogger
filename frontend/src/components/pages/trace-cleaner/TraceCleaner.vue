<template>
  <el-progress
      v-if="traceCleanerStore.loading"
      status="success"
      :text-inside="true"
      :percentage="100"
      :indeterminate="true"
      :duration="5"
      striped
  />
  <el-table
      v-else
      :data="traceCleanerStore.processes"
      :border="true"
  >
    <el-table-column label="Cleared collections/traces">
      <template #default="scope">
        {{ scope.row.cleared_collections_count }}
        <br>
        {{ scope.row.cleared_traces_count }}
      </template>
    </el-table-column>
    <el-table-column label="Created/Updated at" prop="created_at">
      <template #default="scope">
        {{ convertDateStringToLocal(scope.row.created_at, false) }}
        <br>
        {{ convertDateStringToLocal(scope.row.updated_at, false) }}
      </template>
    </el-table-column>
    <el-table-column label="Cleared at" prop="cleared_at">
      <template #default="scope">
        {{ scope.row.cleared_at ? convertDateStringToLocal(scope.row.cleared_at, false) : '' }}
      </template>
    </el-table-column>
    <el-table-column label="Error" prop="error"/>
    <el-table-column label="" width="150" fixed="right">
      <template #header>
        <el-space>
          <el-button
              type="warning"
              :icon="IconRefreshList"
              @click="onUpdate"
              circle
          />
        </el-space>
      </template>
    </el-table-column>
  </el-table>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {useTraceCleanerStore} from "./store/traceCleanerStore.ts";
import {Refresh} from '@element-plus/icons-vue'
import {convertDateStringToLocal} from "../../../utils/helpers.ts";

export default defineComponent({
  data() {
    return {
      store: useTraceCleanerStore(),
    }
  },

  computed: {
    traceCleanerStore() {
      return useTraceCleanerStore()
    },
    IconRefreshList() {
      return Refresh
    },
  },

  methods: {
    convertDateStringToLocal,
    update() {
      this.traceCleanerStore.find()
    },
    onUpdate() {
      this.update()
    },
  },

  mounted() {
    if (!this.traceCleanerStore.loading) {
      return
    }

    this.update()
  },
})
</script>

<style>
</style>
