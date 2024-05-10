<template>
  <el-container>
    <el-progress
        v-if="store.state.loading"
        status="success"
        :text-inside="true"
        :percentage="100"
        :indeterminate="true"
        :duration="5"
        striped
    />
    <div v-else>
      <el-row style="height: 80vh">
        <el-col :span="6">
          <el-row>
            <el-input
                v-model="store.state.profilingTreeFilter"
                placeholder="Filter"
                clearable
            />
          </el-row>
          <el-row>
            <TraceAggregatorSelectedProfilingItem/>
          </el-row>
        </el-col>
        <el-col :span="18" style="width: 100vw; height: 80vh">
          <VueFlow v-model:nodes="store.state.flowItems.nodes" v-model:edges="store.state.flowItems.edges">
            <template #node-custom="props">
              <TraceAggregatorProfilingNode :node="props" />
            </template>

            <MiniMap node-color="black" mask-color="rgba(128, 128, 128, 0.7)" pannable zoomable/>

            <Controls />
          </VueFlow>
        </el-col>
      </el-row>
    </div>
  </el-container>
</template>

<script lang="ts">
import {VueFlow} from '@vue-flow/core'
import {defineComponent} from "vue";
import {useTraceAggregatorProfilingStore} from "../../../../../store/traceAggregatorProfilingStore.ts";
import {MiniMap} from '@vue-flow/minimap'
import { Controls } from '@vue-flow/controls'
import TraceAggregatorSelectedProfilingItem from './TraceAggregatorSelectedProfilingItem.vue'
import TraceAggregatorProfilingNode from './TraceAggregatorProfilingNode.vue'

export default defineComponent({
  components: {
    VueFlow,
    MiniMap,
    Controls,
    TraceAggregatorSelectedProfilingItem,
    TraceAggregatorProfilingNode,
  },

  data() {
    return {
      store: useTraceAggregatorProfilingStore(),
    }
  },

  mounted() {
    // TODO
    this.store.dispatch('findProfiling', {
      traceId: 'slogger-16210198-2451-4106-bd1f-46ff328d7664'
    })
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
