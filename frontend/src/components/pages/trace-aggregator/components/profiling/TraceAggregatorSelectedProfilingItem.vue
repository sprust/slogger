<template>
  <div style="overflow-y: scroll; width: 100vw; height: 75vh">
    <el-tree
        ref="profilingTreeRef"
        :data="store.state.profilingTree"
        :props="treeProps"
        node-key="key"
        :expand-on-click-node="false"
        :filter-node-method="filterTree"
        style="width: 100vw"
    >
      <template #default="{ node }">
        <el-row :class="isSelectedNode(node) ? 'selected-node' : ''" style="width: 100%; font-size: 10px">
          <el-text
              :class="isInHardestCpuFlow(node) ? 'node-flow-hardest-flow' : ''" style="padding-right: 5px"
              truncated
          >
            {{ node.label }}
          </el-text>
          <el-space spacer="|">
            <el-tooltip placement="top-start">
              <template #content>
                <TraceAggregatorProfilingNodeData :item="findItemByNode(node)"/>
              </template>
              <el-icon :size="15">
                <InfoFilled/>
              </el-icon>
            </el-tooltip>
            <el-button type="info" @click="onTreeNodeClick(node)" link>
              flow
            </el-button>
            <el-button type="info" @click="onShowTree(node)" link>
              tree
            </el-button>
          </el-space>
        </el-row>
      </template>
    </el-tree>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {
  ProfilingItem,
  ProfilingTreeNode,
  useTraceAggregatorProfilingStore
} from "../../../../../store/traceAggregatorProfilingStore.ts";
import {ProfilingItemFinder} from "./utils/itemFinder.ts";
import TraceAggregatorProfilingNodeData from "./TraceAggregatorProfilingNodeData.vue";
import {InfoFilled} from '@element-plus/icons-vue'

export default defineComponent({
  components: {TraceAggregatorProfilingNodeData, InfoFilled},

  data() {
    return {
      store: useTraceAggregatorProfilingStore(),
      treeProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },
    }
  },

  methods: {
    filterTree(value: string, data: ProfilingTreeNode) {
      if (!value) {
        return true
      }

      return data.label.includes(value)
    },
    onTreeNodeClick(node: ProfilingTreeNode) {
      if (node.key === this.store.state.selectedItem?.id) {
        this.store.dispatch('setSelectedProfilingItem', null)
      } else {
        const foundItem = (new ProfilingItemFinder()).find(
            node.key, this.store.state.profilingItems
        )

        this.store.dispatch('setSelectedProfilingItem', foundItem)
      }
    },
    isSelectedNode(node: ProfilingTreeNode): boolean {
      return node.key === this.store.state.selectedItem?.id
    },
    isInHardestCpuFlow(node: ProfilingTreeNode): boolean {
      return this.store.state.profilingMetrics.hardestCpuItemIds.indexOf(node.key) !== -1
    },
    onShowTree(node: ProfilingTreeNode) {
      const foundItem = (new ProfilingItemFinder()).find(
          node.key, this.store.state.profilingItems
      )

      this.store.dispatch('setProfilingItems', [foundItem])
    },
    findItemByNode(node: ProfilingTreeNode): ProfilingItem | null {
      return (new ProfilingItemFinder()).find(
          node.key, this.store.state.profilingItems
      )
    },
  },

  watch: {
    'store.state.profilingTreeFilter'(value: string) {
      if (!this.$refs!.profilingTreeRef) {
        return
      }

      // @ts-ignore: doesnt see 'filter' method
      this.$refs!.profilingTreeRef!.filter(value)
    }
  }
})
</script>

<style scoped>
.selected-node {
  font-weight: bold;
}

.node-flow-hardest-flow {
  color: red;
}
</style>
