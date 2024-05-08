<template>
  <el-space spacer="|">
    <el-row>
      <div style="font-weight: bold">
        {{ title }}:
      </div>
      <div style="margin-left: 5px">
        {{ traceId }}
      </div>
    </el-row>
    <el-row>
      <el-button
          @click="$emit('onClickTraceIdTree', traceId)"
          type="info"
          link
      >
        tree
      </el-button>
      <el-button
          @click="$emit('onClickTraceIdFilter', traceId)"
          type="info"
          link
      >
        filter
      </el-button>
    </el-row>
  </el-space>
</template>

<script lang="ts">
import {defineComponent} from 'vue'

export default defineComponent({
  emits: ["onClickTraceIdTree", "onClickTraceIdFilter"],
  props: {
    title: {
      type: String,
      required: true,
    },
    traceId: {
      type: String,
      required: true,
    },
  },
})
</script>

<style scoped>

</style>
