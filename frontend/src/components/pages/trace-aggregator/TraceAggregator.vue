<template>
  <el-tabs v-model="traceAggregatorTabsStore.currentTab" class="height-100">
    <el-tab-pane label="Traces" :name="traceAggregatorTabs.traces" class="height-100">
      <TraceAggregatorTraces/>
    </el-tab-pane>
    <el-tab-pane label="Tree" :name="traceAggregatorTabs.tree" class="height-100">
      <TraceAggregatorTraceTree class="height-100"/>
    </el-tab-pane>
    <el-tab-pane label="Profiling" :name="traceAggregatorTabs.profiling" class="height-100">
      <TraceAggregatorProfiling/>
    </el-tab-pane>
  </el-tabs>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import TraceAggregatorTraces from "./components/traces/TraceAggregatorTraces.vue";
import TraceAggregatorTraceTree from "./components/tree/TraceAggregatorTraceTree.vue";
import TraceAggregatorProfiling from "./components/profiling/TraceAggregatorProfiling.vue";
import FilterTags from "./components/tags/FilterTags.vue";
import {traceAggregatorTabs, useTraceAggregatorTabsStore} from "./store/traceAggregatorTabsStore.ts";

export default defineComponent({
  components: {
    FilterTags,
    TraceAggregatorTraces: TraceAggregatorTraces,
    TraceAggregatorTraceTree,
    TraceAggregatorProfiling
  },

  data() {
    return {
      tabsStore: useTraceAggregatorTabsStore()
    }
  },

  computed: {
    traceAggregatorTabs() {
      return traceAggregatorTabs
    },
    traceAggregatorTabsStore() {
      return useTraceAggregatorTabsStore()
    }
  },
})

</script>

<style scoped>
</style>
