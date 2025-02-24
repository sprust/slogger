<template>
  <div v-if="name">
    {{ name }}
  </div>
  <div v-else>
    !! none !!
  </div>
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
  },
})
</script>

<style scoped>

</style>
