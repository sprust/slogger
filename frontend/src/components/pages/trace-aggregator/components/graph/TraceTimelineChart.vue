<template>
  <Bar
      ref="chartRef"
      :style="`min-height: ${height}; max-height: ${height}`"
      :data="data as any"
      :options="options as any"
      @click="onClick"
  />
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
import {
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  ChartData,
  ChartOptions,
  InteractionItem,
  Legend,
  LinearScale,
  Title,
  Tooltip
} from 'chart.js'
import {Bar, getElementAtEvent} from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

export default defineComponent({
  components: {
    Bar,
  },

  props: {
    data: {
      type: Object as PropType<ChartData>,
      required: true,
    },
    options: {
      type: Object as PropType<ChartOptions>,
      required: true,
    },
    height: {
      type: String,
      required: true,
    },
  },

  emits: ['bar-click'],

  methods: {
    onClick(mouseEvent: MouseEvent) {
      // @ts-ignore TODO
      const chart = this.$refs.chartRef?.chart

      if (!chart) {
        return
      }

      const elements: InteractionItem[] = getElementAtEvent(chart, mouseEvent)

      if (!elements.length) {
        return;
      }

      this.$emit('bar-click', elements[0].index)
    },
  },
})
</script>
