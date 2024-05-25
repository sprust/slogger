<template>
  <el-row>
    <el-select
        v-model="store.state.selectedTimestampPeriod"
        placeholder="Select"
        @change="onChangeTimestampPeriod"
        style="width: 110px"
    >
      <el-option
          v-for="item in store.state.timestampPeriods"
          :key="item.period.name"
          :label="item.period.value"
          :value="item.period.value"
      />
    </el-select>
    <el-select
        v-model="store.state.selectedTimestampStep"
        placeholder="Select"
        style="width: 110px"
    >
      <el-option
          v-for="item in store.state.timestampSteps"
          :key="item.value"
          :label="item.title"
          :value="item.value"
      />
    </el-select>
  </el-row>
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
    },
    onChangeTimestampPeriod() {
      this.store.dispatch('freshTimestampSteps')
    },
  },

  mounted() {
    if (this.store.state.loaded) {
      return
    }

    this.update()
  }
})
</script>

<style scoped>
</style>
