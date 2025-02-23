<template>
  <el-select
      v-model="traceAggregatorStore.state.payload.service_ids"
      placeholder="Select"
      style="min-width: 200px"
      clearable
      multiple
  >
    <el-option
        v-for="item in servicesStore.state.items"
        :key="item.id"
        :label="item.name"
        :value="item.id"
    />
  </el-select>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useTraceAggregatorServicesStore} from "../store/traceAggregatorServicesStore.ts";
import {useTraceAggregatorStore} from "../store/traceAggregatorStore.ts";

export default defineComponent({
  data() {
    return {
      servicesStore: useTraceAggregatorServicesStore(),
      traceAggregatorStore: useTraceAggregatorStore(),
    }
  },
  methods: {
    update() {
      this.servicesStore.dispatch('findServices')
    }
  },
  mounted() {
    if (!this.servicesStore.state.loading) {
      return
    }

    this.update()
  },
})

</script>

<style scoped>

</style>
