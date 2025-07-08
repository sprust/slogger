<template>
  <div class="height-100">
    <el-progress
        v-if="traceAggregatorTreeStore.loading"
        status="success"
        :text-inside="true"
        :percentage="100"
        :indeterminate="true"
        :duration="5"
        striped
    />
    <div v-else-if="traceAggregatorTreeStore.treeNodes.length" class="height-100">
      <el-row style="width: 100%; padding-bottom: 10px">
        <el-space>
          <el-button @click="update">
            Update
          </el-button>
          <div>
            {{ traceAggregatorTreeStore.parameters.trace_id }} ({{ traceAggregatorTreeStore.tracesCount }})
          </div>
        </el-space>
      </el-row>
      <el-row style="padding-bottom: 10px">
        <el-input
            v-model="filterTreeNodeText"
            style="width: 400px; padding-right: 5px"
            placeholder="Filter"
            clearable
        />
        <el-space style="padding-right: 5px">
          <el-select
              v-model="traceAggregatorTreeStore.selectedTraceServiceIds"
              placeholder="Services"
              style="min-width: 200px"
              @change="filterTree"
              clearable
              multiple
          >
            <el-option
                v-for="item in traceAggregatorTreeStore.traceServices"
                :key="item.id"
                :label="item.name"
                :value="item.id"
            />
          </el-select>
        </el-space>
        <el-space>
          <el-select
              v-model="traceAggregatorTreeStore.selectedTraceTypes"
              placeholder="Types"
              style="min-width: 200px"
              @change="filterTree"
              clearable
              multiple
          >
            <el-option
                v-for="type in traceAggregatorTreeStore.traceTypes"
                :key="type"
                :label="type"
                :value="type"
            />
          </el-select>
        </el-space>
        <div class="flex-grow"/>
        <el-text type="info">
          status | logged at | memory | cpu | duration
        </el-text>
      </el-row>
      <el-row style="width: 100%; height: 100%; position: relative;">
        <div class="row-col" style="width: 100%;">
          <el-row v-for="row in traceAggregatorTreeStore.treeNodes" style="width: 100%">
            <div :style="{width: 20 * row.depth + 'px'}"/>

            <div class="trace-tree-metric-indicator" :style="makeTraceIndicatorStyle(row)"/>
            <div class="trace-tree-select-indicator" :style="makeTreeNodeStyle(row)"/>

            <el-space spacer=":" @click="onClickOnRow(row)">
              <TraceService :name="row.service?.name"/>
              <div>
                <el-tag type="success">
                  {{ row.type }}
                </el-tag>
              </div>
              <div v-if="row.tags.length">
                <el-tag v-for="tag in row.tags" type="warning">
                  {{ tag.slice(0, 100) }}
                </el-tag>
              </div>
            </el-space>

            <div class="flex-grow"/>
            <el-space spacer="|">
              <div>
                {{ row.status }}
              </div>
              <div>
                {{ convertDateStringToLocal(row.logged_at) }}
              </div>
              <div>
                {{ row.memory }}
              </div>
              <div>
                {{ row.cpu }}
              </div>
              <div>
                {{ row.duration }}
              </div>
            </el-space>
          </el-row>
        </div>
        <div
            v-if="showData"
            class="row-col right-col"
            style="position: absolute; right: 0; width: 50%;"
        >
          <el-progress
              v-if="traceAggregatorTreeStore.dataLoading"
              status="success"
              :text-inside="true"
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
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {TraceAggregatorTreeRow, useTraceAggregatorTreeStore} from "./store/traceAggregatorTreeStore.ts";
import TraceMetrics from "../traces/TraceItemMetrics.vue";
import TraceService from "../services/TraceService.vue";
import TraceAggregatorTraceDataNode from "../trace/TraceAggregatorTraceDataNode.vue";
import TraceDetail from "../trace/TraceDetail.vue";
import {convertDateStringToLocal} from "../../../../../utils/helpers.ts";

type TreeNodeView = {
  key: string,
  label: string,
  children: null | Array<TreeNodeView>,
}

interface TreeNodeViewsMap {
  [key: string]: TraceAggregatorTreeRow;
}

export default defineComponent({
  components: {TraceDetail, TraceAggregatorTraceDataNode, TraceService, TraceMetrics},
  data() {
    return {
      treeProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },
      treeNodeViewsMap: {} as TreeNodeViewsMap,
      filterTreeNodeText: '',
    }
  },
  computed: {
    traceAggregatorTreeStore() {
      return useTraceAggregatorTreeStore()
    },
    tree(): Array<TreeNodeView> {
      return this.treeNodesToViews(this.traceAggregatorTreeStore.treeNodes)
    },
    showData() {
      return this.traceAggregatorTreeStore.selectedTrace.trace_id || this.traceAggregatorTreeStore.dataLoading
    },
    leftSpan() {
      return this.showData ? 12 : 24
    }
  },
  methods: {
    convertDateStringToLocal,
    update() {
      this.traceAggregatorTreeStore.refreshTree()
    },
    treeNodeTitle(treeNode: TraceAggregatorTreeRow): string {
      return treeNode.type + (treeNode.tags.length ? ` [${treeNode.tags.join(' | ')}]` : '')
    },
    onClickOnRow(treeNode: TraceAggregatorTreeRow) {
      this.traceAggregatorTreeStore.findData(treeNode.trace_id)
    },
    onClickCloseData() {
      this.traceAggregatorTreeStore.resetData()
    },
    treeNodesToViews(treeNodes: Array<TraceAggregatorTreeRow>): Array<TreeNodeView> {
      return treeNodes.map((treeNode: TraceAggregatorTreeRow) => {
        this.treeNodeViewsMap[treeNode.trace_id] = treeNode

        return {
          key: treeNode.trace_id,
          label: this.treeNodeTitle(treeNode),
          // @ts-ignore
          children: this.treeNodesToViews(treeNode.children),
          disabled: false,
        }
      })
    },
    makeTreeNodeStyle(trace: TraceAggregatorTreeRow) {
      const style: { 'background-color'?: string, 'border'?: string } = {}

      if (trace.trace_id === this.traceAggregatorTreeStore.selectedTrace.trace_id) {
        style['background-color'] = 'red'
      }

      if (trace.trace_id === this.traceAggregatorTreeStore.parameters.trace_id) {
        style['border'] = '1px solid green'
      }

      return style
    },
    makeTraceIndicatorStyle(trace: TraceAggregatorTreeRow) {
      let percent = 0

      if (trace.duration && this.traceAggregatorTreeStore.traceIndicatingIds.indexOf(trace.trace_id) !== -1) {
        percent = (trace.duration / this.traceAggregatorTreeStore.traceTotalIndicatorsNumber) * 50
      }

      return {
        width: percent + 'vw',
      }
    },
    filterTree() {
      // @ts-ignore
      this.$refs.traceTreeRef!.filter(this.filterTreeNodeText)
    },
    filterTreeNode(value: string, data: TreeNodeView) {
      if (value && !data.label.includes(value)) {
        return false
      }

      const trace = this.treeNodeViewsMap[data.key]

      if (this.traceAggregatorTreeStore.selectedTraceServiceIds.length) {
        if (!trace.service?.id) {
          return false
        }

        if (this.traceAggregatorTreeStore.selectedTraceServiceIds.indexOf(trace.service.id) === -1) {
          return false
        }
      }

      if (this.traceAggregatorTreeStore.selectedTraceTypes.length
          && this.traceAggregatorTreeStore.selectedTraceTypes.indexOf(trace.type) === -1
      ) {
        return false
      }

      return true
    },
    indicate(treeNode: TraceAggregatorTreeRow) {
      this.traceAggregatorTreeStore.calcTraceIndicators([treeNode])
    }
  },
  watch: {
    'filterTreeNodeText'() {
      this.filterTree()
    }
  },
})
</script>

<style scoped>
.tree-row {
  width: 100%;
  border-bottom: 1px solid rgba(0, 0, 0, .1);;
  padding: 2px;
  cursor: pointer;
}

.row-col {
  height: 90%;
  overflow-y: scroll;
}

.right-col {
  background-color: var(--el-drawer-bg-color);
  --el-drawer-bg-color: var(--el-dialog-bg-color, var(--el-bg-color));
}

.flex-grow {
  flex-grow: 1;
}

.trace-tree-select-indicator {
  margin-right: 3px;
  width: 10px;
  height: 10px;
  border-radius: 20px 20px 20px 20px;
}

.trace-tree-metric-indicator {
  position: absolute;
  display: flex;
  background-color: rgb(139, 0, 0, 30%);
  right: 0;
  height: 20px;
}
</style>
