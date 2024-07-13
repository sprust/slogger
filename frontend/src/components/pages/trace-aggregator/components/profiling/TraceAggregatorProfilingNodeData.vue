<template>
  <div v-if="!item">
    Not found
  </div>
  <el-card v-else class="node-flow" :style="nodeStyle">
    <template #header>
      {{ item.calling }}
    </template>
    <div>
      <TraceAggregatorProfilingNodeMetrics :item="item"/>
    </div>
  </el-card>
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
// @ts-ignore // todo
import {ProfilingNode, useTraceAggregatorProfilingStore} from "../../../../../store/traceAggregatorProfilingStore.ts";
import TraceAggregatorProfilingNodeMetrics from './TraceAggregatorProfilingNodeMetrics.vue'

export default defineComponent({
  components: {TraceAggregatorProfilingNodeMetrics},

  props: {
    item: {
      type: Object as PropType<ProfilingNode | null>,
      nullable: true,
      required: false,
    },
  },

  data() {
    return {
      store: useTraceAggregatorProfilingStore(),
    }
  },

  computed: {
    nodeStyle() {
      let maxWeightPercent = 0

      this.item?.data.map((dataItem) => {
        if (!dataItem.value || this.store.state.showProfilingIndicators.indexOf(dataItem.name) === -1) {
          return 0
        }

        maxWeightPercent = Math.max(maxWeightPercent, dataItem.weight_percent)
      })

      if (maxWeightPercent === 0) {
        return {}
      }

      const roundedPercent = maxWeightPercent - (maxWeightPercent % 10)

      return {
        border: '3px solid',
        'border-color': `rgba(255,0,0,${roundedPercent / 100}`,
      }
    }
  }
})
</script>

<style scoped>
.node-flow {
  width: 200px;
  word-break: break-all;
  height: auto;
}
</style>
