<script lang="ts">

import {defineComponent, PropType} from "vue";
import TraceService from "../services/TraceService.vue";
import {TraceTreeNode} from "./store/traceAggregatorTreeStore.ts";
import TraceAggregatorTraceTreeRow from "./TraceAggregatorTraceTreeRow.vue";

export default defineComponent({
  name: "traceAggregatorTraceTreeRows",

  components: {
    TraceAggregatorTraceTreeRow,
    TraceService,
  },

  props: {
    rows: {
      type: Array as PropType<TraceTreeNode[]>,
      required: true
    },
  },
})
</script>

<template>
  <el-row v-for="row in rows" style="width: 100%; height: 30px" class="current-tree-row">
    <TraceAggregatorTraceTreeRow :row="row"/>
    <TraceAggregatorTraceTreeRows :rows="row.children"/>
  </el-row>
</template>

<style scoped>
:deep(.current-tree-row) {
  &:hover {
    background-color: rgb(139, 139, 139, 0.1);
  }
}
</style>
