<template>
  <el-row style="width: 100%">
    <el-space>
      <el-tooltip content="Reset" placement="top-start">
        <el-button
            @click="onReload"
            :icon="Refresh"
        />
      </el-tooltip>
      <el-button
          @click="onShowTree"
          :icon="traceAggregatorProfilingStore.showTree ? Fold : Expand"
          style="margin-right: 10px"
      />
      <el-text style="padding-right: 5px">
        Show:
      </el-text>
      <el-select
          v-model="traceAggregatorProfilingStore.showProfilingIndicators"
          placeholder="Indicators"
          style="width: auto; min-width: 300px; padding-right: 5px"
          clearable
          multiple
      >
        <el-option
            v-for="indicator in traceAggregatorProfilingStore.profilingIndicators"
            :key="indicator"
            :label="indicator"
            :value="indicator"
        />
      </el-select>
      <el-text style="padding-right: 5px">
        Excluded callers:
      </el-text>
      <el-badge
          style="margin-right: 10px"
          :value="traceAggregatorProfilingStore.requestBody.excluded_callers?.length ?? 0"
          color="green"
      >
        <el-button
            :icon="Plus"
            @click="onAddExcludedCaller"
        />
      </el-badge>
      <el-tooltip content="Filter" placement="top-start">
        <el-button
            :icon="Filter"
            style="margin-right: 10px"
            @click="onFilter"
        />
      </el-tooltip>
    </el-space>
    <div class="flex-grow"/>
    <el-button
        @click="onCloseFlow"
        :disabled="!traceAggregatorProfilingStore.selectedItem"
        :icon="Close"
        link
    >
      Close flow
    </el-button>
  </el-row>
  <el-dialog v-model="traceAggregatorProfilingStore.showExcludedCallerPreviewDialog" title="Exclude caller" width="500">
    <el-row>
      <el-input
          v-model="traceAggregatorProfilingStore.excludedCallerPreview"
          autocomplete="off"
          v-on:keyup.enter="addExcludedCaller"
      >
        <template #append>
          <el-button
              type="primary"
              :icon="Plus"
              @click="addExcludedCaller"
          />
        </template>
      </el-input>
    </el-row>
    <el-row style="margin-top: 5px">
      <el-tag
          v-for="excludedCaller in traceAggregatorProfilingStore.requestBody.excluded_callers ?? []"
          style="margin-right: 5px"
          type="danger"
          closable
          @close="onDeleteExcludedCaller(excludedCaller)"
      >
        <el-space>
          {{ excludedCaller }}
        </el-space>
      </el-tag>
    </el-row>
    <template #footer>
      <el-button
          @click="traceAggregatorProfilingStore.showExcludedCallerPreviewDialog = false; traceAggregatorProfilingStore.excludedCallerPreview = ''">
        Close
      </el-button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useTraceAggregatorProfilingStore} from "./store/traceAggregatorProfilingStore.ts";
import {Close, Refresh, Fold, Expand, Plus, Filter} from '@element-plus/icons-vue'

export default defineComponent({
  components: {Close},

  computed: {
    traceAggregatorProfilingStore() {
      return useTraceAggregatorProfilingStore()
    },
    Close() {
      return Close
    },
    Refresh() {
      return Refresh
    },
    Fold() {
      return Fold
    },
    Expand() {
      return Expand
    },
    Plus() {
      return Plus
    },
    Filter() {
      return Filter
    },
  },

  methods: {
    onReload() {
      this.traceAggregatorProfilingStore.findProfiling(
          this.traceAggregatorProfilingStore.parameters.traceId
      )
    },
    onShowTree() {
      this.traceAggregatorProfilingStore.switchShowTree()
    },
    onAddExcludedCaller() {
      this.traceAggregatorProfilingStore.excludedCallerPreview = ''
      this.traceAggregatorProfilingStore.showExcludedCallerPreviewDialog = true
    },
    onDeleteExcludedCaller(excludedCaller: string) {
      this.traceAggregatorProfilingStore.deleteBodyExcludedCallers(excludedCaller)
    },
    addExcludedCaller() {
      if (!this.traceAggregatorProfilingStore.excludedCallerPreview) {
        return
      }

      this.traceAggregatorProfilingStore.addBodyExcludedCallers(
          this.traceAggregatorProfilingStore.excludedCallerPreview
      )

      this.traceAggregatorProfilingStore.excludedCallerPreview = ''
    },
    onFilter() {
      this.traceAggregatorProfilingStore.findProfilingWithBody()
    },
    onCloseFlow() {
      this.traceAggregatorProfilingStore.setSelectedProfilingItem(null)
    },
  }
})
</script>

<style scoped>
.flex-grow {
  flex-grow: 1;
}
</style>
