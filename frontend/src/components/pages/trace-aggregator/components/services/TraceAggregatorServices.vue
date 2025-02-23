<template>
  <el-select
      v-model="traceAggregatorStore.state.payload.service_ids"
      placeholder="Select"
      style="min-width: 200px"
      clearable
      multiple
  >
    <el-option
        v-for="item in traceAggregatorServicesStore.items"
        :key="item.id"
        :label="item.name"
        :value="item.id"
    />
  </el-select>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useTraceAggregatorServicesStore} from "./store/traceAggregatorServicesStore.ts";
import {useTraceAggregatorStore} from "../traces/store/traceAggregatorStore.ts";

export default defineComponent({
  data() {
    return {
      traceAggregatorStore: useTraceAggregatorStore(),
    }
  },

  computed: {
    traceAggregatorServicesStore() {
      return useTraceAggregatorServicesStore()
    },
  },

  methods: {
    update() {
      this.traceAggregatorServicesStore.findServices()
    }
  },

  mounted() {
    if (!this.traceAggregatorServicesStore.loading) {
      return
    }

    this.update()
  },
})

</script>

<style scoped>

</style>
