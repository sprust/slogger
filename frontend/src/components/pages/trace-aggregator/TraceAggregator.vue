<template>
  <el-tabs v-model="tabsStore.state.currentTab" class="height-100">
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
import TraceAggregatorTraces from "./components/TraceAggregatorTraces.vue";
import TraceAggregatorTraceTree from "./components/TraceAggregatorTraceTree.vue";
import TraceAggregatorProfiling from "./components/profiling/TraceAggregatorProfiling.vue";
import FilterTags from "./widgets/FilterTags.vue";
import {traceAggregatorTabs, useTraceAggregatorTabsStore} from "./store/traceAggregatorTabsStore.ts";

export default defineComponent({
  components: {
    FilterTags,
    TraceAggregatorTraces,
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
    }
  },
  methods: {},
  mounted() {
  }
})

</script>

<style scoped>
</style>
