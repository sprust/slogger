<script lang="ts">
import {defineComponent} from "vue";
import {useTraceAggregatorTreeChildrenStore} from "./store/traceTreeStore.ts";
import {LoadFunction} from "element-plus/es/components/tree/src/tree.type";

type TreeNode = {
  key: string,
  label: string,
  children: null | Array<TreeNode>,
}

export default defineComponent({
  data() {
    return {
      treeProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },
    }
  },

  computed: {
    treeStore() {
      return useTraceAggregatorTreeChildrenStore()
    },
    tree() {

    }
  },

  methods: {
    onLoadNode(node, resolve, reject): LoadFunction {
      if (node.level === 0) {
        return resolve([{ name: 'region' }])
      }

      if (node.level > 1) return resolve([])

      setTimeout(() => {
        const data: Tree[] = [
          {
            name: 'leaf',
            leaf: true,
          },
          {
            name: 'zone',
          },
        ]

        resolve(data)
      }, 500)
    }
  }
})
</script>

<template>
  <el-tree
      style="max-width: 600px"
      :props="treeProps"
      :load="onLoadNode"
      lazy
      show-checkbox
  />
</template>

<style scoped>

</style>
