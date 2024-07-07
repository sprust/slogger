<template>
  <div v-if="!item">
    Not found
  </div>
  <el-tag
      v-for="indicator in indicators"
      type="primary"
      :style="makeIndicatorStyle(indicator)"
  >
    <el-space>
      {{ indicator.name }}: {{ indicator.value }} / {{ indicator.weight_percent }}
    </el-space>
  </el-tag>
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
// @ts-ignore // todo
import {
  ProfilingDataItem,
  ProfilingItem,
  useTraceAggregatorProfilingStore
} from "../../../../../store/traceAggregatorProfilingStore.ts";

export default defineComponent({
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
    indicators() {
      const indicators: Array<ProfilingDataItem> = []

      this.item?.data.map((data: ProfilingDataItem) => {
        if (!data.value
            || this.store.state.profilingMetricsSetting.showProfilingIndicators.indexOf(data.name) === -1
        ) {
          return
        }

        indicators.push(data)
      })

      return indicators
    }
  },

  methods: {
    makeIndicatorStyle(item: ProfilingDataItem) {
      return {
        'font-size': '12px',
        'border-width': '3px',
        'border-color': `rgba(255,0,0,${item.weight_percent / 100}`,

      }
    }
  },
})
</script>

<style scoped>
</style>
