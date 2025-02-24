<template>
  <el-popover placement="bottom" :width="600" trigger="click">
    <template #reference>
      <el-row style="align-items: center;">
        <el-button :icon="Filter" link>
          Other filters
        </el-button>
        <el-tag v-for="filter in titles" type="success" style="margin: 2px">
          {{ filter }}
        </el-tag>
      </el-row>
    </template>
    <el-form label-width="auto">
      <el-form-item label="Trace id">
        <el-input
            v-model="traceAggregatorStore.payload.trace_id"
            :disabled="traceAggregatorGraphStore.showGraph"
            style="width: 450px"
            clearable
        >
          <template #append>
            <el-checkbox
                v-model="traceAggregatorStore.payload.all_traces_in_tree"
                label="Tree"
                :disabled="!traceAggregatorStore.payload.trace_id"
            />
          </template>
        </el-input>
      </el-form-item>
      <el-form-item label="Duration">
        <el-input-number
            v-model="traceAggregatorStore.payload.duration_from"
            :precision="6"
            :step="0.5"
            :min="0"
            controls-position="right"
            class="number-input"
        />
        <el-input-number
            v-model="traceAggregatorStore.payload.duration_to"
            :precision="6"
            :step="0.5"
            :min="0"
            controls-position="right"
            class="number-input"
        />
        <el-button
            :icon="CloseBold"
            @click="onClearDuration"
            style="width: 30px"
        >
        </el-button>
      </el-form-item>
      <el-form-item label="Memory">
        <el-input-number
            v-model="traceAggregatorStore.payload.memory_from"
            :precision="2"
            :step="1"
            :min="0"
            controls-position="right"
            class="number-input"
        />
        <el-input-number
            v-model="traceAggregatorStore.payload.memory_to"
            :precision="2"
            :step="1"
            :min="0"
            controls-position="right"
            class="number-input"
        />
        <el-button
            :icon="CloseBold"
            @click="onClearMemory"
            style="width: 30px"
        >
        </el-button>
      </el-form-item>
      <el-form-item label="Cpu">
        <el-input-number
            v-model="traceAggregatorStore.payload.cpu_from"
            :precision="2"
            :step="5"
            :min="0"
            controls-position="right"
            class="number-input"
        />
        <el-input-number
            v-model="traceAggregatorStore.payload.cpu_to"
            :precision="2"
            :step="5"
            :min="0"
            controls-position="right"
            class="number-input"
        />
        <el-button
            :icon="CloseBold"
            @click="onClearCpu"
            style="width: 30px"
        >
        </el-button>
      </el-form-item>
      <el-form-item label="Profiling">
        <el-switch
            v-model="traceAggregatorStore.payload.has_profiling"
            size="small"
            active-text="has"
            inactive-text="off"
            active-color="green"
        />
      </el-form-item>
    </el-form>
  </el-popover>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useTraceAggregatorStore} from "./store/traceAggregatorStore.ts";
import {CloseBold, Filter} from '@element-plus/icons-vue'
import {useTraceAggregatorGraphStore} from "../graph/store/traceAggregatorGraphStore.ts";
import {makeOtherFiltersTitles} from "../../../../../utils/helpers.ts";

export default defineComponent({
  computed: {
    traceAggregatorStore() {
      return useTraceAggregatorStore()
    },
    traceAggregatorGraphStore() {
      return useTraceAggregatorGraphStore()
    },
    CloseBold() {
      return CloseBold
    },
    Filter() {
      return Filter
    },
    titles(): Array<string> {
      return makeOtherFiltersTitles(this.traceAggregatorStore.payload)
    }
  },

  methods: {
    onClearDuration() {
      this.traceAggregatorStore.clearDurationFilter()
    },
    onClearMemory() {
      this.traceAggregatorStore.clearMemoryFilter()
    },
    onClearCpu() {
      this.traceAggregatorStore.clearCpuFilter()
    },
  }
})
</script>

<style scoped>
.number-input {
  width: 120px;
}
</style>
