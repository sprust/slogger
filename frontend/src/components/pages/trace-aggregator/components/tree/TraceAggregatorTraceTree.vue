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
          <TraceAggregatorTraceTreeVirtual :items="traceAggregatorTreeStore.treeNodes"/>
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
import TraceAggregatorTraceTreeVirtual from "./TraceAggregatorTraceTreeVirtual.vue";

type TreeNodeView = {
  key: string,
  label: string,
  children: null | Array<TreeNodeView>,
}

interface TreeNodeViewsMap {
  [key: string]: TraceAggregatorTreeRow;
}

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
    update() {
      this.traceAggregatorTreeStore.updateTree()
    },
    treeNodeTitle(treeNode: TraceAggregatorTreeRow): string {
      return treeNode.type + (treeNode.tags.length ? ` [${treeNode.tags.join(' | ')}]` : '')
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
        if (!trace?.service_id) {
          return false
        }

        if (this.traceAggregatorTreeStore.selectedTraceServiceIds.indexOf(trace.service_id) === -1) {
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
.row-col {
  height: 90%;
}

.right-col {
  background-color: var(--el-drawer-bg-color);
  --el-drawer-bg-color: var(--el-dialog-bg-color, var(--el-bg-color));
}

.flex-grow {
  flex-grow: 1;
}
</style>
