<template>
  <div style="overflow-y: scroll; width: 100vw; height: 75vh">
    <el-tree
        v-if="!!store.state.selectedItem"
        ref="profilingTreeRef"
        :data="store.state.profilingTree"
        :props="treeProps"
        node-key="key"
        :expand-on-click-node="false"
        default-expand-all
        :filter-node-method="filterTree"
        style="width: 100vw"
        @nodeClick="onTreeNodeClick"
    >
      <template #default="{ node }">
        <div style="font-size: 10px">
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
      console.log(node)
    }
  },

  watch: {
    'store.state.treeFilter'(value: string) {
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

</style>
