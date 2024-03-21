<template>
  <el-form
      label-width="80px"
      style="padding: 10px"
      label-position="left"
  >
    <el-form-item label="Logged at">
      {{ convertDateStringToLocal(trace.logged_at) }}
    </el-form-item>
    <el-form-item label="Id">
      {{ trace.trace_id }}
    </el-form-item>
    <el-form-item v-if="trace.parent_trace_id" label="parent id">
      {{ trace.parent_trace_id }}
    </el-form-item>
    <el-form-item label="Type">
      <el-tag type="success">
        {{ trace.type }}
      </el-tag>
    </el-form-item>
    <el-form-item v-if="trace.tags.length" label="Tags">
      <el-tag v-for="tag in trace.tags" type="warning">
        {{ tag }}
      </el-tag>
    </el-form-item>
    <el-form-item label="Status">
      {{ trace.status }}
    </el-form-item>
    <el-form-item v-if="trace.duration !== null" label="Duration">
      {{ trace.duration }}
    </el-form-item>
  </el-form>
  <TraceAggregatorTraceDataNode :data="trace.data"/>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {TraceAggregatorDetail} from "../../../../store/traceAggregatorDataStore.ts";
import TraceAggregatorTraceDataNode from "../components/TraceAggregatorTraceDataNode.vue";
import {convertDateStringToLocal} from "../../../../utils/helpers.ts";

export default defineComponent({
  methods: {convertDateStringToLocal},
  components: {TraceAggregatorTraceDataNode},
  props: {
    trace: {
      type: Object as PropType<TraceAggregatorDetail>,
      required: true,
    },
  }
})
</script>

<style scoped>

</style>
