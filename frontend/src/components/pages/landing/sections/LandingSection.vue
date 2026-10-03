<template>
  <section :id="anchor" class="landing-section">
    <h2>{{ title }}</h2>
    <p v-for="paragraph in paragraphs" class="landing-paragraph">
      <el-text>{{ paragraph }}</el-text>
    </p>
    <slot/>
  </section>
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";

export default defineComponent({
  props: {
    anchor: {
      type: String,
      required: true,
    },
    title: {
      type: String,
      required: true,
    },
    paragraphs: {
      type: Array as PropType<Array<string>>,
      default: () => [],
    },
  },
})
</script>

<style scoped>
.landing-section {
  padding: 32px 0;
  border-bottom: 1px solid var(--el-border-color-lighter);
}

.landing-paragraph {
  line-height: 1.6;
}
</style>
