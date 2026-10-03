<template>
  <pre class="landing-code">{{ code }}</pre>
</template>

<script lang="ts">
import {defineComponent} from "vue";

export default defineComponent({
  props: {
    code: {
      type: String,
      required: true,
    },
  },
})
</script>

<style scoped>
.landing-code {
  margin: 0;
  padding: 12px 16px;
  overflow-x: auto;
  white-space: pre;
  font-family: var(--el-font-family-mono, monospace);
  color: var(--el-text-color-regular);
  background-color: var(--el-fill-color-light);
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 4px;
}
</style>
