<template>
  <el-tree
      style="width: 100%"
      :data="tree"
      node-key="id"
      default-expand-all
      :expand-on-click-node="false"
      :props="treeProps"
  >
    <template #default="{ data }">
      <el-row style="width: 100%">
        <TraceAggregatorTraceTreeRow :row="data.primary"/>
      </el-row>
    </template>
  </el-tree>
</template>

<script lang="ts">
import {TraceTreeNode} from "./store/traceAggregatorTreeStore.ts";
import TraceAggregatorTraceTreeRow from "./TraceAggregatorTraceTreeRow.vue";
import {defineComponent} from "vue";

export default defineComponent({
  components: {
    TraceAggregatorTraceTreeRow,
  },

  props: {
    tree: {
      type: Array<TraceTreeNode>,
      required: true
    }
  },

  data() {
    return {
      treeProps: {
        key: 'id',
        children: 'children',
        primary: 'primary',
      },
    }
  },
})
</script>

<style scoped>

</style>
