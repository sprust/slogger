<template>
  <el-tooltip placement="top-start">
    <template #content>
      <TraceAggregatorProfilingNodeData :item="node.data"/>
    </template>
    <TraceAggregatorProfilingNodeData :item="node.data"/>
  </el-tooltip>
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
// @ts-ignore // todo
import {Node} from "@vue-flow/core/dist/types/node";
import TraceAggregatorProfilingNodeData from './TraceAggregatorProfilingNodeData.vue'

export default defineComponent({
  name: "TraceAggregatorProfilingNode",

  components: {TraceAggregatorProfilingNodeData},

  props: {
    node: {
      type: Object as PropType<Node>,
      required: true,
    },
  },
})
</script>

<style scoped>

</style>
