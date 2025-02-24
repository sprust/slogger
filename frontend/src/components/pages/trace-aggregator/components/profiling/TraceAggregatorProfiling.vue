<template>
  <div class="height-100">
    <el-progress
        v-if="traceAggregatorProfilingStore.loading && traceAggregatorProfilingStore.parameters.traceId"
        style="width: 100vw"
        status="success"
        :text-inside="true"
        :percentage="100"
        :indeterminate="true"
        :duration="5"
        striped
    />
    <div v-else-if="traceAggregatorProfilingStore.parameters.traceId" class="height-100">
      <el-row style="width: 100%; padding-bottom: 5px">
        <TraceId
            title="Trace id"
            :trace-id="traceAggregatorProfilingStore.parameters.traceId"
            :show-filter-button="false"
            @onClickTraceIdTree="onClickTraceIdTree"
        />
      </el-row>
      <el-row style="width: 100%; padding-bottom: 5px">
        <TraceAggregatorProfilingSetting/>
      </el-row>
      <el-row class="height-100">
        <el-col v-show="traceAggregatorProfilingStore.showTree" :span="leftSpan" class="height-100">
          <el-row class="height-100">
            <TraceAggregatorProfilingTreeV2/>
          </el-row>
        </el-col>
        <el-col v-if="showFlowSpan" :span="rightSpan" style="height: 85vh">
          <VueFlow
              v-model:nodes="traceAggregatorProfilingStore.flowItems.nodes"
              v-model:edges="traceAggregatorProfilingStore.flowItems.edges"
              :min-zoom="0.1"
          >
            <template #node-custom="props">
              <TraceAggregatorProfilingNode :node="props"/>
            </template>

            <template #edge-custom="props">
              <TraceAggregatorProfilingFlowEdge v-bind="props"/>
            </template>

            <MiniMap node-color="black" mask-color="rgba(128, 128, 128, 0.7)" pannable zoomable/>

            <Controls/>
          </VueFlow>
        </el-col>
      </el-row>
    </div>
  </div>
</template>

<script lang="ts">
import {VueFlow} from '@vue-flow/core'
import {defineComponent} from "vue";
import {useTraceAggregatorProfilingStore} from "./store/traceAggregatorProfilingStore.ts";
import {MiniMap} from '@vue-flow/minimap'
import {Controls} from '@vue-flow/controls'
import TraceAggregatorProfilingTreeV2 from './TraceAggregatorProfilingTreeV2.vue'
import TraceAggregatorProfilingNode from './TraceAggregatorProfilingNode.vue'
import TraceAggregatorProfilingSetting from './TraceAggregatorProfilingSetting.vue'
import TraceAggregatorProfilingFlowEdge from './TraceAggregatorProfilingFlowEdge.vue'
import TraceId from "../trace/TraceId.vue";
import {traceAggregatorTabs, useTraceAggregatorTabsStore} from "../../store/traceAggregatorTabsStore.ts";
import {useTraceAggregatorTreeStore} from "../tree/store/traceAggregatorTreeStore.ts";

export default defineComponent({
  components: {
    TraceId,
    VueFlow,
    MiniMap,
    Controls,
    TraceAggregatorProfilingTreeV2,
    TraceAggregatorProfilingNode,
    TraceAggregatorProfilingSetting,
    TraceAggregatorProfilingFlowEdge,
  },

  computed: {
    traceAggregatorTabsStore() {
      return useTraceAggregatorTabsStore()
    },
    traceAggregatorTreeStore() {
      return useTraceAggregatorTreeStore()
    },
    traceAggregatorProfilingStore() {
      return useTraceAggregatorProfilingStore()
    },
    leftSpan() {
      return this.showFlowSpan ? 6 : 24
    },
    rightSpan() {
      return this.traceAggregatorProfilingStore.showTree ? 18 : 24
    },
    showFlowSpan(): boolean {
      return !!this.traceAggregatorProfilingStore.selectedItem
    },
  },

  methods: {
    onClickTraceIdTree(traceId: string) {
      this.traceAggregatorTreeStore.findTreeNodes(traceId)

      this.traceAggregatorTabsStore.setCurrentTab(traceAggregatorTabs.tree)
    },
  },
})
</script>

<style>
/* import the necessary styles for Vue Flow to work */
@import '@vue-flow/core/dist/style.css';

/* import the default theme, this is optional but generally recommended */
@import '@vue-flow/core/dist/theme-default.css';

/* import default minimap styles */
@import '@vue-flow/minimap/dist/style.css';

/* import default controls styles */
@import '@vue-flow/controls/dist/style.css';
</style>
