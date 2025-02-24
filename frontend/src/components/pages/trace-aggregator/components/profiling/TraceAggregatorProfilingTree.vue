<template>
  <div style="overflow-y: scroll; width: 100%; height: 80vh">
    <el-row style="width: 100%; padding-bottom: 10px">
      <el-input
          v-model="filterTreeNodeText"
          style="width: 400px"
          placeholder="Filter"
          clearable
      />
    </el-row>
    <el-tree
        ref="traceProfilingTreeRef"
        style="width: 300vw"
        :data="tree"
        :props="treeProps"
        node-key="key"
        :expand-on-click-node="false"
        :filter-node-method="filterTreeNode"
        default-expand-all
    >
      <template #default="{ data }">
        <el-row :class="isSelectedNode(data) ? 'selected-node' : ''" style="width: 100%">
          <el-text style="padding-right: 5px; font-size: 13px" truncated>
            {{ makeNodeTitle(data.primary) }}
          </el-text>
          <el-space spacer="|">
            <el-button type="info" @click="onFilter(data)" link>
              filter
            </el-button>
            <el-button type="info" @click="onExclude(data)" link>
              exclude
            </el-button>
            <el-button type="info" @click="onShowFlow(data)" link>
              flow
            </el-button>
            <TraceAggregatorProfilingNodeMetrics :item="data.primary"/>
          </el-space>
        </el-row>
      </template>
    </el-tree>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {
  ProfilingNode,
  ProfilingTreeNode,
  useTraceAggregatorProfilingStore
} from "./store/traceAggregatorProfilingStore.ts";
import TraceAggregatorProfilingNodeData from "./TraceAggregatorProfilingNodeData.vue";
import TraceAggregatorProfilingNodeMetrics from './TraceAggregatorProfilingNodeMetrics.vue'

export default defineComponent({
  components: {TraceAggregatorProfilingNodeData, TraceAggregatorProfilingNodeMetrics},

  data() {
    return {
      treeProps: {
        key: 'key',
        label: 'label',
        children: 'children',
        disabled: 'disabled',
        primary: 'primary',
      },
      filterTreeNodeText: '',
    }
  },

  computed: {
    traceAggregatorProfilingStore() {
      return useTraceAggregatorProfilingStore()
    },
    tree(): Array<ProfilingTreeNode> {
      return this.treeNodesToViews(this.traceAggregatorProfilingStore.profiling.nodes)
    },
  },

  methods: {
    treeNodesToViews(treeNodes: Array<ProfilingNode>): Array<ProfilingTreeNode> {
      return treeNodes.map((treeNode: ProfilingNode) => {
        return {
          key: treeNode.id,
          label: treeNode.calling,
          // @ts-ignore
          children: treeNode.children ? this.treeNodesToViews(treeNode.children) : null,
          disabled: false,
          primary: treeNode
        }
      })
    },
    makeNodeTitle(node: ProfilingNode): string {
      return node.id + ': ' + node.calling + (node.recursionNodeId ? ` ---> ${node.recursionNodeId}` : '')
    },
    isSelectedNode(node: ProfilingNode): boolean {
      return node.id === this.traceAggregatorProfilingStore.selectedItem?.id
    },
    onShowFlow(node: ProfilingTreeNode) {
      if (node.primary.id === this.traceAggregatorProfilingStore.selectedItem?.id) {
        this.traceAggregatorProfilingStore.setSelectedProfilingItem(null)
      } else {
        this.traceAggregatorProfilingStore.setSelectedProfilingItem(node.primary)
      }
    },
    onFilter(node: ProfilingTreeNode) {
      this.traceAggregatorProfilingStore.setBodyCaller(node.primary.calling)
      this.traceAggregatorProfilingStore.findProfilingWithBody()
    },
    onExclude(node: ProfilingTreeNode) {
      this.traceAggregatorProfilingStore.showExcludedCallerPreviewDialog = true

      this.traceAggregatorProfilingStore.excludedCallerPreview = node.primary.calling
    },
    filterTreeNode(value: string, data: ProfilingTreeNode) {
      if (!value) {
        return true
      }

      return data.primary.calling.includes(value)
    }
  },
  watch: {
    'filterTreeNodeText'(value: string) {
      // @ts-ignore
      this.$refs.traceProfilingTreeRef!.filter(value)
    }
  },
})
</script>

<style scoped>
.selected-node {
  font-weight: bold;
}
</style>
