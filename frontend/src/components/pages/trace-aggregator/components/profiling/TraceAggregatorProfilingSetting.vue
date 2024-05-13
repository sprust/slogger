<template>
  <el-row style="width: 100%">
    <el-button
        @click="onReload"
        :icon="Refresh"
        style="margin-right: 10px"
    />
    <el-text style="padding-right: 5px">
      Show:
    </el-text>
    <el-select
        v-model="store.state.profilingMetricsSetting.showProfilingIndicators"
        placeholder="Indicators"
        style="width: auto; min-width: 300px"
        clearable
        multiple
    >
      <el-option
          v-for="indicator in store.state.profilingIndicators"
          :key="indicator"
          :label="indicator"
          :value="indicator"
      />
    </el-select>
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
import {Close, Refresh} from '@element-plus/icons-vue'

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
    },
    Refresh() {
      return Refresh
    },
  },

  methods: {
    onReload() {
      this.store.dispatch('findProfiling', {
        traceId: this.store.state.parameters.traceId
      })
    },
    onCloseFlow() {
      this.store.dispatch('setSelectedProfilingItem', null)
    },
  }
})
</script>

<style scoped>
.flex-grow {
  flex-grow: 1;
}
</style>
