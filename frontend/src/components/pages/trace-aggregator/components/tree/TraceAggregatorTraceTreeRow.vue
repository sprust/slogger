<script lang="ts">

import {defineComponent, PropType} from "vue";
import TraceService from "../services/TraceService.vue";
import {TraceAggregatorTreeRow, useTraceAggregatorTreeStore} from "./store/traceAggregatorTreeStore.ts";
import {convertDateStringToLocal} from "../../../../../utils/helpers.ts";

export default defineComponent({
  components: {TraceService},

  props: {
    row: {
      type: Object as PropType<TraceAggregatorTreeRow>,
      required: true
    }
  },

  computed: {
    traceAggregatorTreeStore() {
      return useTraceAggregatorTreeStore()
    }
  },

  methods: {
    convertDateStringToLocal,
    makeTreeNodeStyle(trace: TraceAggregatorTreeRow) {
      const style: { 'background-color'?: string, 'border'?: string } = {}

      if (trace.trace_id === this.traceAggregatorTreeStore.selectedTrace.trace_id) {
        style['background-color'] = 'red'
      }

      if (trace.trace_id === this.traceAggregatorTreeStore.parameters.trace_id) {
        style['border'] = '1px solid green'
      }

      return style
    },
    makeTraceIndicatorStyle(trace: TraceAggregatorTreeRow) {
      let percent = 0

      if (trace.duration && this.traceAggregatorTreeStore.traceIndicatingIds.indexOf(trace.trace_id) !== -1) {
        percent = (trace.duration / this.traceAggregatorTreeStore.traceTotalIndicatorsNumber) * 50
      }

      return {
        width: percent + 'vw',
      }
    },
    onClickOnRow(treeNode: TraceAggregatorTreeRow) {
      this.traceAggregatorTreeStore.findData(treeNode.trace_id)
    },
  }
})
</script>

<template>
  <el-space>
    <div :style="{width: 20 * row.depth + 'px'}"/>
    <div class="trace-tree-metric-indicator" :style="makeTraceIndicatorStyle(row)"/>
    <div class="trace-tree-select-indicator" :style="makeTreeNodeStyle(row)"/>
  </el-space>

  <el-space spacer=":" @click="onClickOnRow(row)">
    <TraceService :name="row.service?.name"/>
    <div>
      <el-tag type="success">
        {{ row.type }}
      </el-tag>
    </div>
    <div v-if="row.tags.length">
      <el-tag v-for="tag in row.tags" type="warning">
        {{ tag.slice(0, 100) }}
      </el-tag>
    </div>
  </el-space>

  <div class="flex-grow"/>

  <el-space spacer="|">
    <div>
      {{ row.status }}
    </div>
    <div>
      {{ convertDateStringToLocal(row.logged_at) }}
    </div>
    <div>
      {{ row.memory }}
    </div>
    <div>
      {{ row.cpu }}
    </div>
    <div>
      {{ row.duration }}
    </div>
  </el-space>
</template>

<style scoped>
.trace-tree-select-indicator {
  margin-right: 3px;
  width: 10px;
  height: 10px;
  border-radius: 20px 20px 20px 20px;
}

.trace-tree-metric-indicator {
  position: absolute;
  display: flex;
  background-color: rgb(139, 0, 0, 30%);
  right: 0;
  height: 20px;
}
.flex-grow {
  flex-grow: 1;
}
</style>
