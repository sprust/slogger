<template>
  <div v-if="!item">
    Not found
  </div>
  <el-tag
      v-if="item?.number_of_calls"
      type="primary"
  >
    calls: {{ item.number_of_calls }}
  </el-tag>
  <el-tag
      v-if="item?.wait_time_in_us "
      type="primary"
  >
    time: {{ item.wait_time_in_us }}us
  </el-tag>
  <el-tag
      v-if="item?.cpu_time"
      type="primary"
  >
    cpu: {{ item.cpu_time }}
  </el-tag>
  <el-tag
      v-if="item?.memory_usage_in_bytes"
      type="primary"
  >
    mem: {{ item.memory_usage_in_bytes }}b
  </el-tag>
  <el-tag
      v-if="item?.peak_memory_usage_in_bytes"
      type="primary"
  >
    mem peak: {{ item.peak_memory_usage_in_bytes }}b
  </el-tag>
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
// @ts-ignore // todo
import {ProfilingItem} from "../../../../../store/traceAggregatorProfilingStore.ts";

export default defineComponent({
  props: {
    item: {
      type: Object as PropType<ProfilingItem | null>,
      nullable: true,
      required: false,
    },
  },
})
</script>

<style scoped>
</style>
