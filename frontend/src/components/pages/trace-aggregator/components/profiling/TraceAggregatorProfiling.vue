<template>
  <el-container>
    <el-progress
        v-if="store.state.loading"
        style="width: 100vw"
        status="success"
        :text-inside="true"
        :percentage="100"
        :indeterminate="true"
        :duration="5"
        striped
    />
    <div v-else style="width: 100vw">
      <el-row style="width: 100%; padding-bottom: 5px">
        <TraceAggregatorProfilingSetting/>
      </el-row>
      <el-row style="width: 100%">
        <el-col :span="leftSpan">
          <el-row>
            <el-input
                v-model="store.state.profilingTreeFilterPrev"
                placeholder="Filter ('*' - any symbols, '!' in start - is exclude, ';' - separator)"
                v-on:keyup.enter="onFilterTree"
                clearable
            >
              <template #append>
                <el-button
                    :icon="Search"
                    @click="onFilterTree"
                />
              </template>
            </el-input>
          </el-row>
          <el-row>
            <TraceAggregatorProfilingTree/>
          </el-row>
        </el-col>
        <el-col v-if="showFlowSpan" :span="18" style="width: 100%; height: 70vh">
          <VueFlow
              v-model:nodes="store.state.flowItems.nodes"
              v-model:edges="store.state.flowItems.edges"
              :min-zoom="0.1"
          >
            <template #node-custom="props">
              <TraceAggregatorProfilingNode :node="props"/>
            </template>

            <MiniMap node-color="black" mask-color="rgba(128, 128, 128, 0.7)" pannable zoomable/>

            <Controls/>
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
import {Controls} from '@vue-flow/controls'
import TraceAggregatorProfilingTree from './TraceAggregatorProfilingTree.vue'
import TraceAggregatorProfilingNode from './TraceAggregatorProfilingNode.vue'
import TraceAggregatorProfilingSetting from './TraceAggregatorProfilingSetting.vue'
import {Search} from '@element-plus/icons-vue'

export default defineComponent({
  components: {
    VueFlow,
    MiniMap,
    Controls,
    TraceAggregatorProfilingTree,
    TraceAggregatorProfilingNode,
    TraceAggregatorProfilingSetting,
  },

  data() {
    return {
      store: useTraceAggregatorProfilingStore(),
    }
  },

  computed: {
    leftSpan() {
      return this.showFlowSpan ? 6 : 24
    },
    showFlowSpan(): boolean {
      return !!this.store.state.selectedItem
    },
    Search() {
      return Search
    }
  },

  methods: {
    onFilterTree() {
      this.store.dispatch('setProfilingTreeFilter', this.store.state.profilingTreeFilterPrev)
    }
  },

  mounted() {
    if (!this.store.state.loading) {
      return
    }

    // TODO
    this.store.dispatch('findProfiling', {
      traceId: 'slogger-3c3690ff-e2c3-4dfc-990c-b3b9eb0a3d2f'
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
