<template>
  <el-text v-if="name" :type="isSelected ? 'primary' : ''">
    {{ name }}
  </el-text>
  <el-text v-else>
    !! none !!
  </el-text>
</template>

<script lang="ts">
import {defineComponent} from 'vue'

export default defineComponent({
  props: {
    name: {
      type: String,
      required: false,
      default: null
    },
    isSelected: {
      type: Boolean,
      required: false,
      default: false
    },
  },
})
</script>

<style scoped>

</style>
