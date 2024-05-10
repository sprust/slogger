<template>
  <el-card :class="'node-flow ' + (isInHardestCpuFlow ? 'node-flow-hardest-flow' : '')">
    <template #header>
      <el-tooltip :content="node.label" placement="top-start">
        {{ node.label.substring(node.label.length - 50) }}
      </el-tooltip>
    </template>
    <div>
      <el-tag
          v-if="item.number_of_calls"
          type="primary"
      >
        calls: {{ item.number_of_calls }}
      </el-tag>
      <el-tag
          v-if="item.wait_time_in_us "
          type="primary"
      >
        time: {{ item.wait_time_in_us }}us
      </el-tag>
      <el-tag
          v-if="item.cpu_time"
          type="primary"
      >
        cpu: {{ item.cpu_time }}
      </el-tag>
      <el-tag
          v-if="item.memory_usage_in_bytes"
          type="primary"
      >
        mem: {{ item.memory_usage_in_bytes }}b
      </el-tag>
      <el-tag
          v-if="item.peak_memory_usage_in_bytes"
          type="primary"
      >
        mem peak: {{ item.peak_memory_usage_in_bytes }}b
      </el-tag>
    </div>
  </el-card>
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
// @ts-ignore // todo
import {Node} from "@vue-flow/core/dist/types/node";
import {ProfilingItem, useTraceAggregatorProfilingStore} from "../../../../../store/traceAggregatorProfilingStore.ts";

export default defineComponent({
  props: {
    node: {
      type: Object as PropType<Node>,
      required: true,
    },
  },

  data() {
    return {
      item: this.node.data as ProfilingItem,
      store: useTraceAggregatorProfilingStore(),
    }
  },

  computed: {
    isInHardestCpuFlow() {
      return this.store.state.profilingMetrics.hardestCpuItemIds.indexOf(this.item.id) !== -1
    }
  }
})
</script>

<style scoped>
.node-flow {
  width: 200px;
  word-break: break-all;
  height: auto;
}
.node-flow-hardest-flow {
  color: red;
}
</style>
