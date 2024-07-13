<template>
  <div style="overflow-y: scroll; width: 100%; height: 80vh">
    <el-tree
        :data="tree"
        :props="treeProps"
        node-key="key"
        :expand-on-click-node="false"
        style="width: 300vw"
        default-expand-all
    >
      <template #default="{ node, data }">
        <el-row :class="isSelectedNode(data) ? 'selected-node' : ''" style="width: 100%">
          <el-text style="padding-right: 5px; font-size: 13px" truncated>
            {{ makeNodeTitle(data.primary) }}
          </el-text>
          <el-space spacer="|">
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
} from "../../../../../store/traceAggregatorProfilingStore.ts";
import TraceAggregatorProfilingNodeData from "./TraceAggregatorProfilingNodeData.vue";
import TraceAggregatorProfilingNodeMetrics from './TraceAggregatorProfilingNodeMetrics.vue'

export default defineComponent({
  components: {TraceAggregatorProfilingNodeData, TraceAggregatorProfilingNodeMetrics},

  data() {
    return {
      store: useTraceAggregatorProfilingStore(),
      treeProps: {
        key: 'key',
        label: 'label',
        children: 'children',
        disabled: 'disabled',
        primary: 'primary',
      },
    }
  },

  computed: {
    tree(): Array<ProfilingTreeNode> {
      return this.treeNodesToViews(this.store.state.profiling.nodes)
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
      return node.id === this.store.state.selectedItem?.id
    },
    onShowFlow(node: ProfilingTreeNode) {
      if (node.primary.id === this.store.state.selectedItem?.id) {
        this.store.dispatch('setSelectedProfilingItem', null)
      } else {
        this.store.dispatch('setSelectedProfilingItem', node.primary)
      }
    }
  },
})
</script>

<style scoped>
.selected-node {
  font-weight: bold;
}
</style>
