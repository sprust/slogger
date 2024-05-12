<template>
  <div v-if="!item">
    Not found
  </div>
  <el-tag
      v-for="indicator in indicators"
      type="primary"
  >
    {{ indicator.name }}: {{ indicator.value }}
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
  }
})
</script>

<style scoped>
</style>
