<template>
  <el-row v-if="duration != null">
    {{ duration }}
    <div class="flex-grow"/>
    DUR
  </el-row>
  <el-row :style="makeBackgroundColorStyle(memory)">
    {{ memory }}
    <div class="flex-grow"/>
    MEM
  </el-row>
  <el-row :style="makeBackgroundColorStyle(cpu)">
    {{ cpu }}
    <div class="flex-grow"/>
    CPU
  </el-row>
</template>

<script lang="ts">
import {defineComponent} from 'vue'

export default defineComponent({
  props: {
    duration: {
      type: Number,
      required: false,
      default: null
    },
    memory: {
      type: Number,
      required: false,
      default: null
    },
    cpu: {
      type: Number,
      required: false,
      default: null
    },
  },
  methods: {
    makeBackgroundColorStyle(percent: number | null): string {
      if (!percent) {
        return ''
      }

      const opacity = percent / 100

      if (opacity <= 0.2) {
        return ''
      }

      return `background-color: rgba(255, 0, 0, ${opacity - 0.2})`
    }
  }
})
</script>

<style scoped>
.flex-grow {
  flex-grow: 1;
}
</style>
