<template>
  <div style="overflow-y: scroll; width: 100%; height: 75vh">
    <el-tree
        v-if="!!store.state.selectedItem"
        ref="profilingTreeRef"
        :data="tree"
        :props="treeProps"
        node-key="key"
        :expand-on-click-node="false"
        default-expand-all
        :filter-node-method="filterTree"
    >
      <template #default="{ node }">
        <div style="font-size: 10px">
          {{ node.label.substring(node.label.length - 50) }}
        </div>
      </template>
    </el-tree>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {ProfilingItem, useTraceAggregatorProfilingStore} from "../../../../../store/traceAggregatorProfilingStore.ts";

type TreeNode = {
  key: string,
  label: string,
  children: null | Array<TreeNode>,
  disabled: boolean
}

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
    tree(): Array<TreeNode> {
      if (!this.store.state.selectedItem) {
        return []
      }

      return this.dataNodeToTree([this.store.state.selectedItem])
    },
  },

  methods: {
    dataNodeToTree(items: Array<ProfilingItem>): Array<TreeNode> {
      return items.map((item: ProfilingItem) => {
        return {
          key: item.id,
          label: item.call,
          children: this.dataNodeToTree(item.callables),
          disabled: false,
        }
      })
    },
    filterTree(value: string, data: TreeNode) {
      if (!value) {
        return true
      }

      return data.label.includes(value)
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
