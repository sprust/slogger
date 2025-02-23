<template>
  <el-row>
    <el-select
        v-model="traceAggregatorTimestampPeriodStore.selectedTimestampPeriod"
        placeholder="Select"
        @change="onChangeTimestampPeriod"
        style="width: 110px"
    >
      <el-option
          v-for="item in traceAggregatorTimestampPeriodStore.timestampPeriods"
          :key="item.period.name"
          :label="item.period.value"
          :value="item.period.value"
      />
    </el-select>
    <el-select
        v-model="traceAggregatorTimestampPeriodStore.selectedTimestampStep"
        placeholder="Select"
        style="width: 110px"
    >
      <el-option
          v-for="item in traceAggregatorTimestampPeriodStore.timestampSteps"
          :key="item.value"
          :label="item.title"
          :value="item.value"
      />
    </el-select>
  </el-row>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useTraceAggregatorTimestampPeriodStore} from "./store/traceAggregatorTimestampPeriodsStore.ts";

export default defineComponent({
  methods: {
    update() {
      this.traceAggregatorTimestampPeriodStore.findTimestampPeriods()
    },
    onChangeTimestampPeriod() {
      this.traceAggregatorTimestampPeriodStore.freshTimestampSteps()
    },
  },

  computed: {
    traceAggregatorTimestampPeriodStore() {
      return useTraceAggregatorTimestampPeriodStore()
    },
  },

  mounted() {
    if (this.traceAggregatorTimestampPeriodStore.loaded) {
      return
    }

    this.update()
  }
})
</script>

<style scoped>
</style>
