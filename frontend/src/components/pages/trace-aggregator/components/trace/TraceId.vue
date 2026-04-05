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
      <el-space>
        <el-dropdown trigger="click">
          <el-space>
            <el-button
                type="info"
                link
            >
              tree
            </el-button>
          </el-space>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item @click="$emit('onClickTraceIdTreeParent', traceId)">
                parent
              </el-dropdown-item>
              <el-dropdown-item @click="$emit('onClickTraceIdTreeCurrent', traceId)">
                current
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
        <el-button
            v-if="showFilterButton"
            @click="$emit('onClickTraceIdFilter', traceId)"
            type="info"
            link
        >
          filter
        </el-button>
      </el-space>
    </el-row>
  </el-space>
</template>

<script lang="ts">
import {defineComponent} from 'vue'

export default defineComponent({
  emits: ["onClickTraceIdTreeParent", "onClickTraceIdTreeCurrent", "onClickTraceIdFilter"],
  props: {
    title: {
      type: String,
      required: true,
    },
    traceId: {
      type: String,
      required: true,
    },
    showFilterButton: {
      type: Boolean,
      required: false,
      default: true,
    },
  },
})
</script>

<style scoped>

</style>
