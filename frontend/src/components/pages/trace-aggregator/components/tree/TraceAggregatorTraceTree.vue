<template>
  <div class="height-100">
    <div class="height-100">
      <el-row style="width: 100%; padding-bottom: 10px">
        <el-text v-if="isTraceSelected">
          {{ traceAggregatorTreeStore.parameters.trace_id }} ({{ traceAggregatorTreeStore.content.count }})
        </el-text>
        <div class="flex-grow"/>
        <el-button
            v-if="isTraceSelected"
            link
            class="tree-cancel-button"
            :disabled="!traceAggregatorTreeStore.polling"
            @click="cancel"
        >
          Cancel
        </el-button>
        <el-button
            v-if="isTraceSelected"
            @click="fresh"
            :disabled="inProcess"
            link
        >
          Fresh
        </el-button>
        <el-button
            v-if="isTraceSelected"
            @click="update"
            :icon="UpdateIcon"
            :disabled="inProcess"
        >
          Update
        </el-button>
        <el-button
            @click="onShowProcessesDialog"
            :icon="ProcessesIcon"
        >
          Processes
        </el-button>
      </el-row>
      <el-progress
          v-if="inProcess"
          :stroke-width="10"
          status="success"
          percentage="100"
          :indeterminate="true"
          :duration="5"
          striped
          style="padding-bottom: 10px"
      >
        {{ traceAggregatorTreeStore.state?.count ?? 0 }}
      </el-progress>
      <el-row v-else-if="isTraceSelected" style="padding-bottom: 10px">
        <el-space style="padding-right: 5px">
          <el-select
              v-model="traceAggregatorTreeStore.selectedTraceServiceIds"
              :placeholder="'Services ' + '(' + traceAggregatorTreeStore.content.services.length + ')'"
              style="min-width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              multiple
          >
            <el-option
                v-for="item in traceAggregatorTreeStore.content.services"
                :key="item.id"
                :label="item.name + ' (' + item.traces_count + ')'"
                :value="item.id"
            />
          </el-select>
        </el-space>
        <el-space>
          <el-select
              v-model="traceAggregatorTreeStore.selectedTraceTypes"
              :placeholder="'Types ' + '(' + traceAggregatorTreeStore.content.types.length + ')'"
              style="min-width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              multiple
          >
            <el-option
                v-for="item in traceAggregatorTreeStore.content.types"
                :key="item.name"
                :label="item.name + ' (' + item.traces_count + ')'"
                :value="item.name"
            />
          </el-select>
        </el-space>
        <el-space>
          <el-select
              v-model="traceAggregatorTreeStore.selectedTraceTags"
              :placeholder="'Tags ' + '(' + traceAggregatorTreeStore.content.tags.length + ')'"
              style="min-width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              multiple
          >
            <el-option
                v-for="item in traceAggregatorTreeStore.content.tags"
                :key="item.name"
                :label="item.name + ' (' + item.traces_count + ')'"
                :value="item.name"
            />
          </el-select>
        </el-space>
        <el-space>
          <el-select
              v-model="traceAggregatorTreeStore.selectedTraceStatuses"
              :placeholder="'Statuses ' + '(' + traceAggregatorTreeStore.content.statuses.length + ')'"
              style="min-width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              multiple
          >
            <el-option
                v-for="item in traceAggregatorTreeStore.content.statuses"
                :key="item.name"
                :label="item.name + ' (' + item.traces_count + ')'"
                :value="item.name"
            />
          </el-select>
          <el-button @click="applyFilters">
            Apply
          </el-button>
        </el-space>
        <div class="flex-grow"/>
        <el-text type="info">
          status | logged at | memory | cpu | duration
        </el-text>
      </el-row>
      <el-row v-if="!inProcess" style="width: 100%; height: 100%; position: relative;">
        <div v-if="traceAggregatorTreeStore.treeNodes.length" class="row-col" style="width: 100%;">
          <TraceAggregatorTraceTreeVirtual :items="traceAggregatorTreeStore.filteredTree"/>
        </div>
        <el-alert
            v-else-if="!traceAggregatorTreeStore.dataLoading && traceAggregatorTreeStore.state?.status === 'failed'"
            style="height: 100px"
            title="Tree build failed"
            type="error"
            :description="traceAggregatorTreeStore.state?.error ?? ''"
            :closable="false"
            show-icon
        />
        <el-alert
            v-else-if="!traceAggregatorTreeStore.dataLoading && traceAggregatorTreeStore.state?.status === 'canceled'"
            style="height: 100px"
            title="Tree build canceled"
            type="warning"
            :closable="false"
            show-icon
        />
        <div
            v-if="showData"
            class="row-col right-col"
            style="position: absolute; right: 0; width: 50%;"
        >
          <el-progress
              v-if="traceAggregatorTreeStore.dataLoading"
              status="success"
              :percentage="100"
              :indeterminate="true"
              :duration="5"
              striped
          />
          <div v-else style="padding: 10px">
            <el-row>
              <el-button @click="onClickCloseData">
                Close
              </el-button>
            </el-row>
            <TraceDetail :trace="traceAggregatorTreeStore.selectedTrace"/>
          </div>
        </div>
      </el-row>
    </div>

    <el-dialog
        v-model="showProcessesDialog"
        width="80%"
        top="10px"
        :append-to-body="true"
        @open="updateProcesses"
    >
      <template #header>
        <el-space>
          <el-text size="default">
            Tree processes (last 50 non-finished)
          </el-text>
          <el-button
              :icon="UpdateIcon"
              size="small"
              @click="updateProcesses"
              :loading="traceAggregatorTreeProcessesStore.loading"
          />
        </el-space>
      </template>

      <el-table
          :data="traceAggregatorTreeProcessesStore.processes"
          style="height: 80vh; width: 100%"
      >
        <el-table-column label="Root trace id" prop="root_trace_id" min-width="220"/>
        <el-table-column label="Count" prop="count" min-width="80"/>
        <el-table-column label="Error" prop="error" min-width="220"/>
        <el-table-column label="Status" min-width="120">
          <template #default="props">
            <el-text :type="makeProcessStatusType(props.row.status)">
              {{ props.row.status }}
            </el-text>
          </template>
        </el-table-column>
        <el-table-column label="Created at" prop="created_at" min-width="180"/>
        <el-table-column label="Started at" prop="started_at" min-width="180"/>
        <el-table-column label="Finished at" prop="finished_at" min-width="180"/>
        <el-table-column fixed="right" min-width="150">
          <template #default="props">
            <el-space>
              <el-button
                  v-if="props.row.status === 'inProcess'"
                  size="small"
                  type="warning"
                  link
                  @click="cancelProcess(props.row.root_trace_id)"
              >
                Cancel
              </el-button>
              <el-button
                  size="small"
                  type="danger"
                  link
                  @click="deleteProcess(props.row.root_trace_id)"
              >
                Delete
              </el-button>
            </el-space>
          </template>
        </el-table-column>
      </el-table>
    </el-dialog>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useTraceAggregatorTreeStore} from "./store/traceAggregatorTreeStore.ts";
import {
  useTraceAggregatorTreeProcessesStore
} from "./store/traceAggregatorTreeProcessesStore.ts";
import TraceMetrics from "../traces/TraceItemMetrics.vue";
import TraceService from "../services/TraceService.vue";
import TraceAggregatorTraceDataNode from "../trace/TraceAggregatorTraceDataNode.vue";
import TraceDetail from "../trace/TraceDetail.vue";
import TraceAggregatorTraceTreeVirtual from "./TraceAggregatorTraceTreeVirtual.vue";
import {List, Refresh as UpdateIcon} from '@element-plus/icons-vue'

export default defineComponent({
  components: {
    TraceDetail,
    TraceAggregatorTraceDataNode,
    TraceService,
    TraceMetrics,
    TraceAggregatorTraceTreeVirtual
  },

  data() {
    return {
      treeProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },
      showProcessesDialog: false
    }
  },
  computed: {
    traceAggregatorTreeStore() {
      return useTraceAggregatorTreeStore()
    },
    traceAggregatorTreeProcessesStore() {
      return useTraceAggregatorTreeProcessesStore()
    },
    showData() {
      return this.traceAggregatorTreeStore.selectedTrace.trace_id || this.traceAggregatorTreeStore.dataLoading
    },
    inProcess() {
      return this.traceAggregatorTreeStore.loading || this.traceAggregatorTreeStore.polling
    },
    isTraceSelected() {
      return !!this.traceAggregatorTreeStore.parameters.trace_id
    },
    leftSpan() {
      return this.showData ? 12 : 24
    },
    UpdateIcon() {
      return UpdateIcon
    },
    ProcessesIcon() {
      return List
    }
  },

  methods: {
    update() {
      this.traceAggregatorTreeStore.updateTree()
    },
    fresh() {
      this.traceAggregatorTreeStore.freshTree()
    },
    cancel() {
      this.traceAggregatorTreeStore.cancelPolling()
    },
    onShowProcessesDialog() {
      this.showProcessesDialog = true
    },
    updateProcesses() {
      this.traceAggregatorTreeProcessesStore.findProcesses()
    },
    cancelProcess(rootTraceId: string) {
      this.traceAggregatorTreeProcessesStore.cancelProcess(rootTraceId)
          .then(() => this.updateProcesses())
    },
    deleteProcess(rootTraceId: string) {
      if (!confirm('Do you want delete process?')) {
        return
      }

      this.traceAggregatorTreeProcessesStore.deleteProcess(rootTraceId)
          .then(() => this.updateProcesses())
    },
    makeProcessStatusType(status: string): 'warning' | 'danger' | 'info' {
      if (status === 'inProcess') {
        return 'warning'
      }

      if (status === 'failed') {
        return 'danger'
      }

      return 'info'
    },
    onClickCloseData() {
      this.traceAggregatorTreeStore.resetSelectedTrace()
    },
    applyFilters() {
      this.traceAggregatorTreeStore.applyFilters()
    }
  },
})
</script>

<style scoped>
.row-col {
  height: 90%;
  overflow-y: auto;
}

.right-col {
  background-color: var(--el-drawer-bg-color);
  --el-drawer-bg-color: var(--el-dialog-bg-color, var(--el-bg-color));
}

.flex-grow {
  flex-grow: 1;
}

.tree-cancel-button {
  color: var(--el-color-danger);
}

.tree-cancel-button.is-disabled {
  color: var(--el-text-color-disabled);
}
</style>
