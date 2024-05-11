<template>
  <el-row style="width: 100%">
    <el-text style="padding-right: 5px">
      Show:
    </el-text>
    <el-space spacer="|">
      <el-switch
          v-model="store.state.profilingMetricsSetting.showNumberOfCalls"
          class="ml-2"
          inline-prompt
          active-text="calls"
          inactive-text="calls"
          active-color="#13ce66"
      />
      <el-switch
          v-model="store.state.profilingMetricsSetting.showWaitTimeInUs"
          class="ml-2"
          inline-prompt
          active-text="time"
          inactive-text="time"
          active-color="#13ce66"
      />
      <el-switch
          v-model="store.state.profilingMetricsSetting.showCpuTime"
          class="ml-2"
          inline-prompt
          active-text="cpu"
          inactive-text="cpu"
          active-color="#13ce66"
      />
      <el-switch
          v-model="store.state.profilingMetricsSetting.showMemoryUsageInBytes"
          class="ml-2"
          inline-prompt
          active-text="mem"
          inactive-text="mem"
          active-color="#13ce66"
      />
      <el-switch
          v-model="store.state.profilingMetricsSetting.showPeakMemoryUsageInBytes"
          class="ml-2"
          inline-prompt
          active-text="mem peak"
          inactive-text="mem peak"
          active-color="#13ce66"
      />
    </el-space>
    <div class="flex-grow"/>
    <el-button
        @click="onCloseFlow"
        :disabled="!store.state.selectedItem"
        :icon="Close"
        link
    >
      Close flow
    </el-button>
  </el-row>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useTraceAggregatorProfilingStore} from "../../../../../store/traceAggregatorProfilingStore.ts";
import {Close} from '@element-plus/icons-vue'

export default defineComponent({
  components: {Close},

  data() {
    return {
      store: useTraceAggregatorProfilingStore(),
    }
  },

  computed: {
    Close() {
      return Close
    }
  },

  methods: {
    onCloseFlow() {
      this.store.dispatch('setSelectedProfilingItem', null)
    }
  }
})
</script>

<style scoped>
.flex-grow {
  flex-grow: 1;
}
</style>
