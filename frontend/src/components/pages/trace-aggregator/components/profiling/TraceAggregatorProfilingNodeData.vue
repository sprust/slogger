<template>
  <div v-if="!item">
    Not found
  </div>
  <el-card v-else :class="'node-flow ' + (isInHardestFlow ? 'node-flow-hardest-flow' : '')">
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
import {ProfilingItem, useTraceAggregatorProfilingStore} from "../../../../../store/traceAggregatorProfilingStore.ts";
import TraceAggregatorProfilingNodeMetrics from './TraceAggregatorProfilingNodeMetrics.vue'

export default defineComponent({
  components: {TraceAggregatorProfilingNodeMetrics},

  props: {
    item: {
      type: Object as PropType<ProfilingItem | null>,
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
    isInHardestFlow(): boolean {
      if (!this.item) {
        return false
      }

      return this.store.state.profilingMetrics.hardestItemIds.indexOf(this.item.id) !== -1
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

.node-flow-hardest-flow {
  color: red;
}
</style>
