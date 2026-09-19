<template>
  <span>{{ parts[0] }}<span class="data-match">{{ parts[1] }}</span>{{ parts[2] }}</span>
</template>

<script lang="ts">
import {defineComponent} from 'vue'

/**
 * A piece of a data row with the searched substring marked, without handing markup to
 * v-html: the text arrives split into before, match and after.
 */
export default defineComponent({
  name: 'DataNodeText',

  props: {
    text: {
      type: String,
      required: true,
    },
    query: {
      type: String,
      required: false,
      default: '',
    },
  },

  computed: {
    parts(): Array<string> {
      const query: string = this.query.trim().toLowerCase()

      if (!query) {
        return [this.text, '', '']
      }

      const at: number = this.text.toLowerCase().indexOf(query)

      if (at < 0) {
        return [this.text, '', '']
      }

      return [
        this.text.slice(0, at),
        this.text.slice(at, at + query.length),
        this.text.slice(at + query.length),
      ]
    },
  },
})
</script>

<style scoped>
.data-match:not(:empty) {
  border-radius: 2px;
  background-color: var(--el-color-warning-light-5);
  color: var(--el-text-color-primary);
}
</style>
