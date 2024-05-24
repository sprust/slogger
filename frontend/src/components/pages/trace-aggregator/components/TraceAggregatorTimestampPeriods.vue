<template>
  <el-select
      v-model="storeGraph.state.payload.timestamp_period"
      placeholder="Select"
  >
    <el-option
        v-for="item in store.state.timestampPeriods"
        :key="item.name"
        :label="item.value"
        :value="item.value"
    />
  </el-select>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useTraceAggregatorTimestampPeriodStore} from "../../../../store/traceAggregatorTimestampPeriodsStore.ts";
import {useTraceAggregatorGraphStore} from "../../../../store/traceAggregatorGraphStore.ts";

export default defineComponent({
  data() {
    return {
      store: useTraceAggregatorTimestampPeriodStore(),
      storeGraph: useTraceAggregatorGraphStore(),
    }
  },
  methods: {
    update() {
      this.store.dispatch('findTimestampPeriods')
    }
  },
  mounted() {
    this.update()
  }
})
</script>

<style scoped>
</style>
