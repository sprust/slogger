<template>
  <div v-if="!item">
    Not found
  </div>
  <el-card v-else :class="'node-flow ' + (isInHardestCpuFlow ? 'node-flow-hardest-flow' : '')">
    <template #header>
      {{ item.call }}
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
import {ProfilingItem, useTraceAggregatorProfilingStore} from "../../../../../store/traceAggregatorProfilingStore.ts";

export default defineComponent({
  name: "TraceAggregatorProfilingNode",

  props: {
    item: {
      type: Object as PropType<ProfilingItem>,
      nullable: true,
      required: false,
    },
  },

  data() {
    return {
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
