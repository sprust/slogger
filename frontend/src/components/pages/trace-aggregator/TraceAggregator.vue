<template>
  <el-tabs v-model="tabsStore.state.currentTab">
    <el-tab-pane label="Traces" :name="traceAggregatorTabs.traces">
      <TraceAggregatorTraces/>
    </el-tab-pane>
    <el-tab-pane label="Tree" :name="traceAggregatorTabs.tree">
      <TraceAggregatorTraceTree/>
    </el-tab-pane>
  </el-tabs>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import TraceAggregatorTraces from "./components/TraceAggregatorTraces.vue";
import TraceAggregatorTraceTree from "./components/TraceAggregatorTraceTree.vue";
import FilterTags from "./widgets/FilterTags.vue";
import {traceAggregatorTabs, useTraceAggregatorTabsStore} from "../../../store/traceAggregatorTabsStore.ts";

export default defineComponent({
  components: {FilterTags, TraceAggregatorTraces: TraceAggregatorTraces, TraceAggregatorTraceTree},
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
