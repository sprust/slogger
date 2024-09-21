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
            v-model="store.state.payload.trace_id"
            :disabled="storeGraph.state.showGraph"
            style="width: 450px"
            clearable
        >
          <template #append>
            <el-checkbox
                v-model="store.state.payload.all_traces_in_tree"
                label="Tree"
                :disabled="!store.state.payload.trace_id"
            />
          </template>
        </el-input>
      </el-form-item>
      <el-form-item label="Duration">
        <el-input-number
            v-model="store.state.payload.duration_from"
            :precision="6"
            :step="0.5"
            :min="0"
            controls-position="right"
            class="number-input"
        />
        <el-input-number
            v-model="store.state.payload.duration_to"
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
            v-model="store.state.payload.memory_from"
            :precision="2"
            :step="1"
            :min="0"
            controls-position="right"
            class="number-input"
        />
        <el-input-number
            v-model="store.state.payload.memory_to"
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
            v-model="store.state.payload.cpu_from"
            :precision="2"
            :step="5"
            :min="0"
            controls-position="right"
            class="number-input"
        />
        <el-input-number
            v-model="store.state.payload.cpu_to"
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
            v-model="store.state.payload.has_profiling"
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
import {useTraceAggregatorStore} from "../../../../store/traceAggregatorStore.ts";
import {CloseBold, Filter} from '@element-plus/icons-vue'
import {useTraceAggregatorGraphStore} from "../../../../store/traceAggregatorGraphStore.ts";
import {valueIsSelected} from "../../../../utils/valueWasSelected.ts";

export default defineComponent({
  data() {
    return {
      store: useTraceAggregatorStore(),
      storeGraph: useTraceAggregatorGraphStore(),
    }
  },

  computed: {
    CloseBold() {
      return CloseBold
    },
    Filter() {
      return Filter
    },
    titles(): Array<string> {
      const titles = new Array<string>()

      const payload = this.store.state.payload

      if (payload.trace_id) {
        titles.push(
            'Trace id: ' + payload.trace_id + (payload.all_traces_in_tree ? ' (tree)' : '')
        )
      }

      const durationFromSelected = valueIsSelected(payload.duration_from)
      const durationToSelected = valueIsSelected(payload.duration_to)

      if (durationFromSelected || durationToSelected) {
        titles.push(
            'Duration: ' + (durationFromSelected ? payload.duration_from : '∞') + '-'
            + (durationToSelected ? payload.duration_to : '∞')
        )
      }

      const memoryFromSelected = valueIsSelected(payload.memory_from)
      const memoryToSelected = valueIsSelected(payload.memory_to)

      if (memoryFromSelected || memoryToSelected) {
        titles.push(
            'Memory: ' + (memoryFromSelected ? payload.memory_from : '∞') + '-'
            + (memoryToSelected ? payload.memory_to : '∞')
        )
      }

      const cpuFromSelected = valueIsSelected(payload.cpu_from)
      const cpuToSelected = valueIsSelected(payload.cpu_to)

      if (cpuFromSelected || cpuToSelected) {
        titles.push(
            'Cpu: ' + (cpuFromSelected ? payload.cpu_from : '∞') + '-'
            + (cpuToSelected ? payload.cpu_to : '∞')
        )
      }

      if (payload.has_profiling) {
        titles.push('Has profiling')
      }

      return titles
    }
  },

  methods: {
    onClearDuration() {
      this.store.commit('clearDurationFilter')
    },
    onClearMemory() {
      this.store.commit('clearMemoryFilter')
    },
    onClearCpu() {
      this.store.commit('clearCpuFilter')
    },
  }
})
</script>

<style scoped>
.number-input {
  width: 120px;
}
</style>
