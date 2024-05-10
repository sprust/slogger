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
            <el-select
                v-model="store.state.selectedItemId"
                placeholder="Select"
                style="width: 100%; font-size: 12px"
                clearable
                @change="onSelect"
            >
              <el-option
                  v-for="item in store.state.profilingItems"
                  :key="item.call"
                  :label="item.call"
                  :value="item.call"
              />
            </el-select>
          </el-row>
          <el-row>
            <TraceAggregatorSelectedProfilingItem/>
          </el-row>
        </el-col>
        <el-col :span="18" style="width: 100vw; height: 80vh">
          <VueFlow v-model:nodes="nodes" v-model:edges="edges">
            <MiniMap node-color="black" pannable zoomable/>
          </VueFlow>
        </el-col>
      </el-row>
    </div>
  </el-container>
</template>

<script lang="ts">
import {VueFlow} from '@vue-flow/core'
import {defineComponent} from "vue";
import {ProfilingItem, useTraceAggregatorProfilingStore} from "../../../../../store/traceAggregatorProfilingStore.ts";
import {FlowBuilder} from "./flowBuilder.ts";
import {MiniMap} from '@vue-flow/minimap'
import TraceAggregatorSelectedProfilingItem from './TraceAggregatorSelectedProfilingItem.vue'

export default defineComponent({
  components: {VueFlow, MiniMap, TraceAggregatorSelectedProfilingItem},

  data() {
    const nodes = [
      {id: '1', type: 'input', label: 'Node 1', position: {x: 250, y: 5}},
      {id: '2', label: 'Node 2', position: {x: 100, y: 100}},
      {id: '3', label: 'Node 3', position: {x: 400, y: 100}},
      {id: '4', label: 'Node 4', position: {x: 400, y: 200}},
    ]

    const edges = [
      {id: 'e1-2', source: '1', target: '2', animated: true},
      {id: 'e1-3', source: '1', target: '3'},
    ]

    return {
      nodes,
      edges,
      store: useTraceAggregatorProfilingStore(),
    }
  },

  methods: {
    onSelect() {
      this.freshFlow()
    },
    freshFlow() {
      let foundItem = null

      if (this.store.state.selectedItemId) {
        foundItem = this.store.state.profilingItems.find((item: ProfilingItem) => {
          return item.call === this.store.state.selectedItemId
        })
      }

      this.store.dispatch('setSelectedProfilingItem', foundItem)

      if (!foundItem) {
        this.nodes = []
        this.edges = []

        return
      }

      const flow = (new FlowBuilder([foundItem])).build()

      this.nodes = flow.nodes
      this.edges = flow.edges
    },
  },

  mounted() {
    // TODO
    this.store.dispatch('findProfiling', {
      traceId: 'slogger-c586b4a3-390a-47fe-9958-800f88f42300'
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
</style>
