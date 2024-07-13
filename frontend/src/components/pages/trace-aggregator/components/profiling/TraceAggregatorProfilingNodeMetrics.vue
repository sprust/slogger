<template>
  <div v-if="!item">
    Not found
  </div>
  <el-tag
      v-for="indicator in indicators"
      type="primary"
  >
    <el-space>
      {{ indicator.name }}: {{ indicator.value }}
      <el-tooltip>
        <template #content>
          {{ indicator.weight_percent }}%
        </template>
        <div class="metric-indicator" :style="makeIndicatorStyle(indicator)"></div>
      </el-tooltip>
    </el-space>
  </el-tag>
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
// @ts-ignore // todo
import {
  ProfilingNodeDataItem,
  ProfilingNode,
  useTraceAggregatorProfilingStore
} from "../../../../../store/traceAggregatorProfilingStore.ts";

export default defineComponent({
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
    indicators() {
      const indicators: Array<ProfilingNodeDataItem> = []

      this.item?.data.map((data: ProfilingNodeDataItem) => {
        if (!data.value || this.store.state.showProfilingIndicators.indexOf(data.name) === -1) {
          return
        }

        indicators.push(data)
      })

      return indicators
    }
  },

  methods: {
    makeIndicatorStyle(item: ProfilingNodeDataItem) {
      const roundedPercent = item.weight_percent - (item.weight_percent % 10)

      return {
        'background-color': `rgba(255,0,0,${roundedPercent / 100}`,
      }
    }
  },
})
</script>

<style scoped>
.metric-indicator {
  width: 15px;
  height: 15px;
  border-radius: 20px 20px 20px 20px;
  border: 1px solid rgba(255, 255, 255, .3);
}
</style>
