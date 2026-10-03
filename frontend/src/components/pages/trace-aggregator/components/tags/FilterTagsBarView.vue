<template>
  <el-form :inline="true">
    <el-form-item
        v-for="section in sections"
        :key="section.key"
        :label="`${section.label}:`"
    >
      <el-tooltip
          v-for="value in visibleOf(section.selectedTags)"
          :key="value"
          :content="value"
          :disabled="value.length <= maxTagLength"
          placement="top"
      >
        <el-check-tag
            :type="section.tagType"
            :checked="true"
            @click="$emit('tag-click', section.key, value)"
        >
          {{ truncate(value) }}
        </el-check-tag>
      </el-tooltip>
      <el-tooltip
          v-if="hiddenOf(section.selectedTags).length"
          placement="top"
      >
        <template #content>
          <div v-for="hidden in hiddenOf(section.selectedTags)" :key="hidden">
            {{ hidden }}
          </div>
        </template>
        <el-check-tag
            :type="section.tagType"
            :checked="true"
            @click="$emit('open-dialog')"
        >
          +{{ hiddenOf(section.selectedTags).length }}
        </el-check-tag>
      </el-tooltip>
    </el-form-item>
    <el-form-item>
      <el-button :icon="TagAddIcon" @click="$emit('open-dialog')"/>
    </el-form-item>
  </el-form>
</template>

<script lang="ts">
import {defineComponent, PropType, shallowRef} from "vue";
import {Plus as TagAddIcon} from '@element-plus/icons-vue'
import type {TraceTagHistoryType} from "./store/traceAggregatorTagsStore.ts";

export interface FilterTagsBarSection {
  key: TraceTagHistoryType,
  label: string,
  tagType: string,
  selectedTags: Array<string>,
}

export default defineComponent({
  props: {
    sections: {
      type: Array as PropType<Array<FilterTagsBarSection>>,
      required: true,
    },
  },

  emits: ['tag-click', 'open-dialog'],

  data() {
    return {
      TagAddIcon: shallowRef(TagAddIcon),
      maxTagLength: 30,
      maxVisibleTags: 2,
    }
  },

  methods: {
    visibleOf(values: string[] | undefined): string[] {
      return (values ?? []).slice(0, this.maxVisibleTags)
    },
    hiddenOf(values: string[] | undefined): string[] {
      return (values ?? []).slice(this.maxVisibleTags)
    },
    truncate(value: string): string {
      return value.length > this.maxTagLength
          ? `${value.slice(0, this.maxTagLength)}…`
          : value
    },
  },
})
</script>
