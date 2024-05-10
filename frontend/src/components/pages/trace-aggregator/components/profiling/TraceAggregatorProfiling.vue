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
                v-model="store.state.selectedRootItemId"
                placeholder="Select"
                style="width: 100%; font-size: 12px"
                clearable
                @change="onSelectRootItem"
            >
              <el-option
                  v-for="item in store.state.profilingItems"
                  :key="item.id"
                  :label="item.call"
                  :value="item.id"
              />
            </el-select>
          </el-row>
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
import {useTraceAggregatorProfilingStore} from "../../../../../store/traceAggregatorProfilingStore.ts";
import {MiniMap} from '@vue-flow/minimap'
import TraceAggregatorSelectedProfilingItem from './TraceAggregatorSelectedProfilingItem.vue'
import {ProfilingItemFinder} from "./utils/itemFinder.ts";

export default defineComponent({
  components: {VueFlow, MiniMap, TraceAggregatorSelectedProfilingItem},

  data() {
    return {
      store: useTraceAggregatorProfilingStore(),
    }
  },

  methods: {
    onSelectRootItem() {
      const foundItem = (new ProfilingItemFinder()).find(
          this.store.state.selectedRootItemId, this.store.state.profilingItems
      )

      this.store.dispatch('setSelectedProfilingItem', foundItem)
      this.store.dispatch('buildProfilingTree', foundItem)
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
