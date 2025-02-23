<template>
  <el-space>
    <el-text size="small">
      Pages:
    </el-text>
    <el-button
        size="small"
        :disabled="disabled || currentPage == 1"
        @click="onPrevPage"
    >
      <el-icon class="el-icon--left">
        <ArrowLeft/>
      </el-icon>
      prev
    </el-button>
    <el-text size="small">
      {{ currentPage }}
    </el-text>
    <el-button
        size="small"
        :disabled="disabled"
        class="el-icon--right"
        @click="onNextPage"
    >
      next
      <el-icon class="el-icon--right">
        <ArrowRight/>
      </el-icon>
    </el-button>
  </el-space>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {ArrowLeft, ArrowRight} from '@element-plus/icons-vue'

export default defineComponent({
  components: {
    ArrowLeft,
    ArrowRight
  },

  props: {
    disabled: {
      type: Boolean,
      required: true,
    },
    currentPage: {
      type: Number,
      required: true,
    },
  },

  emits: ["onNextPage", "onPrevPage"],

  methods: {
    onNextPage() {
      this.$emit("onNextPage")
    },
    onPrevPage() {
      this.$emit("onPrevPage")
    }
  }
})
</script>

<style scoped>
</style>
