<template>
  <div v-if="!item">
    Not found
  </div>
  <el-card v-else class="node-flow" :style="nodeStyle">
    <template #header>
      {{ item.id }}: {{ item.calling }}
    </template>
    <div>
      <TraceAggregatorProfilingNodeMetrics :item="item"/>
    </div>
  </el-card>
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
// @ts-ignore // todo
import {ProfilingNode, useTraceAggregatorProfilingStore} from "./store/traceAggregatorProfilingStore.ts";
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

  computed: {
    traceAggregatorProfilingStore() {
      return useTraceAggregatorProfilingStore()
    },
    nodeStyle() {
      let totalPercent = 0
      let totalCount = 0

      this.item?.data.map((dataItem) => {
        if (!dataItem.value
            || this.traceAggregatorProfilingStore.showProfilingIndicators.indexOf(dataItem.name) === -1
        ) {
          return 0
        }

        totalPercent += dataItem.weight_percent
        totalCount += 1
      })

      if (totalCount === 0) {
        return 0
      }

      const roundedPercent = parseFloat((totalPercent / totalCount).toPrecision(5))

      return {
        border: '3px solid',
        'border-color': `rgba(255,0,0,${roundedPercent}`,
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
