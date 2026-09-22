<template>
  <div class="height-100">
    <div class="height-100">
      <el-row style="width: 100%; padding-bottom: 10px">
        <el-text v-if="isTraceSelected">
          {{ traceAggregatorTreeStore.parameters.trace_id }}
          ({{ traceAggregatorTreeStore.state?.count ?? traceAggregatorTreeStore.content.count }})
        </el-text>
        <el-text v-if="traceAggregatorTreeStore.lazy" type="warning" style="padding-left: 12px">
          {{ lazyNote }}
        </el-text>
        <div class="flex-grow"/>
        <el-button
            v-if="isTraceSelected"
            link
            class="tree-cancel-button"
            :disabled="!traceAggregatorTreeStore.building"
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
            v-if="isTraceSelected"
            @click="showTreeJson"
            :icon="JsonIcon"
            :disabled="inProcess || !traceAggregatorTreeStore.tree.length"
        >
          JSON
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
          <el-select-v2
              v-model="traceAggregatorTreeStore.selectedTraceServiceIds"
              :options="serviceOptions"
              :placeholder="'Services (' + contentCount('services') + ')'"
              :disabled="traceAggregatorTreeStore.contentLoading"
              style="width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              filterable
              multiple
          />
        </el-space>
        <el-space>
          <el-select-v2
              v-model="traceAggregatorTreeStore.selectedTraceTypes"
              :options="typeOptions"
              :placeholder="'Types (' + contentCount('types') + ')'"
              :disabled="traceAggregatorTreeStore.contentLoading"
              style="width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              filterable
              multiple
          />
        </el-space>
        <el-space>
          <el-select-v2
              v-model="traceAggregatorTreeStore.selectedTraceTags"
              :options="tagOptions"
              :placeholder="'Tags (' + contentCount('tags') + ')'"
              :disabled="traceAggregatorTreeStore.contentLoading"
              style="width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              filterable
              multiple
          />
        </el-space>
        <el-space>
          <el-select-v2
              v-model="traceAggregatorTreeStore.selectedTraceStatuses"
              :options="statusOptions"
              :placeholder="'Statuses (' + contentCount('statuses') + ')'"
              :disabled="traceAggregatorTreeStore.contentLoading"
              style="width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              filterable
              multiple
          />
          <el-button
              :disabled="traceAggregatorTreeStore.filtering || traceAggregatorTreeStore.expanding"
              @click="applyFilters"
          >
            Apply
          </el-button>
          <el-dropdown
              trigger="click"
              :disabled="inProcess || !traceAggregatorTreeStore.tree.length || traceAggregatorTreeStore.expanding || traceAggregatorTreeStore.filtering"
              @command="expandToLevel"
          >
            <el-button
                :disabled="inProcess || !traceAggregatorTreeStore.tree.length || traceAggregatorTreeStore.expanding || traceAggregatorTreeStore.filtering"
            >
              Levels
            </el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-for="level in expandLevels" :key="level" :command="level">
                  Level {{ level }}
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </el-space>
        <div class="flex-grow"/>
        <el-text type="info">
          status | logged at | memory | cpu | duration
        </el-text>
      </el-row>
      <el-row v-if="!inProcess" style="width: 100%; height: 100%; position: relative;">
        <div v-if="traceAggregatorTreeStore.tree.length" class="row-col" style="width: 100%;">
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
            class="right-col"
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
          <TraceDetail v-else :trace="traceAggregatorTreeStore.selectedTrace">
            <template #actions>
              <el-button @click="onClickCloseData">
                Close
              </el-button>
            </template>
          </TraceDetail>
        </div>
      </el-row>
    </div>

    <el-dialog
        v-model="jsonDialogVisible"
        width="80%"
        top="10px"
        :append-to-body="true"
        destroy-on-close
    >
      <JsonViewer
          v-if="traceAggregatorTreeStore.jsonNodes"
          :value="jsonValue"
          :expand-depth="jsonExpandDepth"
          style="height: 80vh"
      />
    </el-dialog>

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
        <el-table-column label="Created at" prop="created_at" min-width="180"/>
        <el-table-column label="Started at" prop="started_at" min-width="180"/>
        <el-table-column label="Finished at" prop="finished_at" min-width="180"/>
        <el-table-column label="Status" min-width="120">
          <template #default="props">
            <el-text :type="makeProcessStatusType(props.row.status)">
              {{ props.row.status }}
            </el-text>
          </template>
        </el-table-column>
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
import {LAZY_EXPAND_NODES_LIMIT, useTraceAggregatorTreeStore} from "./store/traceAggregatorTreeStore.ts";
import {
  useTraceAggregatorTreeProcessesStore
} from "./store/traceAggregatorTreeProcessesStore.ts";
import TraceMetrics from "../traces/TraceItemMetrics.vue";
import TraceService from "../services/TraceService.vue";
import TraceAggregatorTraceDataNode from "../trace/TraceAggregatorTraceDataNode.vue";
import TraceDetail from "../trace/TraceDetail.vue";
import TraceAggregatorTraceTreeVirtual from "./TraceAggregatorTraceTreeVirtual.vue";
import JsonViewer from "../../../../json/JsonViewer.vue";
import {TreeJsonBuilder} from "./store/TreeJsonBuilder.ts";
import {Document as JsonIcon, List, Refresh as UpdateIcon} from '@element-plus/icons-vue'

const largeTreeTraces = 300

const largeTreeExpandDepth = 4

const expandLevelsCount = 10

export default defineComponent({
  components: {
    TraceDetail,
    TraceAggregatorTraceDataNode,
    TraceService,
    TraceMetrics,
    TraceAggregatorTraceTreeVirtual,
    JsonViewer
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
      return this.traceAggregatorTreeStore.loading || this.traceAggregatorTreeStore.building
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
    },
    JsonIcon() {
      return JsonIcon
    },
    jsonDialogVisible: {
      get(): boolean {
        return this.traceAggregatorTreeStore.jsonNodes !== null
      },
      set(visible: boolean) {
        if (!visible) {
          this.traceAggregatorTreeStore.hideJson()
        }
      },
    },
    // A virtual list: a tree can carry thousands of tags, and plain options render all.
    serviceOptions() {
      return this.traceAggregatorTreeStore.content.services.map(item => ({
        label: `${item.name} (${item.traces_count})`,
        value: item.id,
      }))
    },
    typeOptions() {
      return this.makeOptions(this.traceAggregatorTreeStore.content.types)
    },
    tagOptions() {
      return this.makeOptions(this.traceAggregatorTreeStore.content.tags)
    },
    statusOptions() {
      return this.makeOptions(this.traceAggregatorTreeStore.content.statuses)
    },
    expandLevels(): number[] {
      return Array.from({length: expandLevelsCount}, (_, index) => index + 1)
    },
    lazyNote(): string {
      const store = this.traceAggregatorTreeStore

      const notes = ['large tree: branches load on expand; filters cover the whole tree; indicate and json cover loaded nodes']

      if (store.filtering) {
        notes.push('filtering...')
      } else if (store.lazyFilter) {
        notes.push(
            store.lazyFilter.truncated
                ? `showing the first ${store.lazyFilter.matchedCount} matches`
                : `${store.lazyFilter.matchedCount} ${store.lazyFilter.matchedCount === 1 ? 'match' : 'matches'}`
        )
      }

      if (store.expanding) {
        notes.push('expanding...')
      } else if (store.expandStopped) {
        notes.push(`expanding stopped at ${LAZY_EXPAND_NODES_LIMIT} loaded nodes`)
      }

      return notes.join('; ')
    },
    jsonValue(): unknown {
      return new TreeJsonBuilder(this.traceAggregatorTreeStore.servicesMap)
          .build(this.traceAggregatorTreeStore.jsonNodes ?? [])
    },
    jsonExpandDepth(): number {
      const count = new TreeJsonBuilder(this.traceAggregatorTreeStore.servicesMap)
          .count(this.traceAggregatorTreeStore.jsonNodes ?? [])

      return count > largeTreeTraces ? largeTreeExpandDepth : Infinity
    },
  },

  methods: {
    update() {
      this.traceAggregatorTreeStore.updateTree()
    },
    fresh() {
      this.traceAggregatorTreeStore.freshTree()
    },
    cancel() {
      this.traceAggregatorTreeStore.cancelBuild()
    },
    onShowProcessesDialog() {
      this.showProcessesDialog = true
    },
    showTreeJson() {
      this.traceAggregatorTreeStore.showTreeJson()
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
    },
    makeOptions(items: Array<{name: string, traces_count: number}>) {
      return items.map(item => ({
        label: `${item.name} (${item.traces_count})`,
        value: item.name,
      }))
    },
    contentCount(field: 'services' | 'types' | 'tags' | 'statuses'): string {
      if (this.traceAggregatorTreeStore.contentLoading) {
        return 'loading...'
      }

      return String(this.traceAggregatorTreeStore.content[field].length)
    },
    expandToLevel(level: number) {
      this.traceAggregatorTreeStore.expandToLevel(level)
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
  height: 90%;
  overflow: hidden;
  display: flex;
  flex-direction: column;
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
