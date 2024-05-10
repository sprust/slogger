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
        @nodeClick="onTreeNodeClick"
    >
      <template #default="{ node }">
        <div :class="isSelectedNode(node) ? 'selected-node' : ''" style="font-size: 10px">
          {{ node.label }}
        </div>
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

export default defineComponent({
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

  computed: {
    tree(): Array<ProfilingTreeNode> {
      if (!this.store.state.selectedItem) {
        return []
      }

      return this.dataNodeToTree([this.store.state.selectedItem])
    },
  },

  methods: {
    dataNodeToTree(items: Array<ProfilingItem>): Array<ProfilingTreeNode> {
      return items.map((item: ProfilingItem) => {
        return {
          key: item.id,
          label: item.call,
          children: this.dataNodeToTree(item.callables),
          disabled: false,
        }
      })
    },
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
    }
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
</style>
