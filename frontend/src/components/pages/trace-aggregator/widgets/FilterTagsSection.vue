<template>
  <el-form @submit.prevent="findTags">
    <el-form-item :label="title">
    </el-form-item>
    <el-form-item>
      <el-input v-model="searchQuery" clearable>
        <template #append>
          <el-button :icon="SearchIcon" @click="findTags" :loading="loading.loading"/>
        </template>
      </el-input>
    </el-form-item>
    <el-form-item>
      <el-row>
        <el-check-tag
            v-for="tag in tags"
            :key="tag.name"
            :type="tagType"
            :checked="selectedTags.indexOf(tag.name) !== -1"
            @click="onTagClick(tag.name)"
        >
          {{ tag.name }} ({{ tag.count }})
        </el-check-tag>
      </el-row>
    </el-form-item>
  </el-form>
</template>

<script lang="ts">
import {defineComponent, PropType, shallowRef} from "vue";
import {Plus as TagAddIcon, Search as SearchIcon} from '@element-plus/icons-vue'
import {TagLoading, TraceTag} from "../../../../store/traceAggregatorTagsStore.ts";

export default defineComponent({
  emits: ['findTags', 'onTagClick'],
  props: {
    title: {
      type: String,
      required: true,
    },
    tagType: {
      type: String,
      required: true,
    },
    tags: {
      type: Array<TraceTag>,
      required: true,
    },
    selectedTags: {
      type: Array<string>,
      required: true,
    },
    loading: {
      type: Object as PropType<TagLoading>,
      required: true,
    },
  },
  data() {
    return {
      searchQuery: '',
      TagAddIcon: shallowRef(TagAddIcon),
      SearchIcon: shallowRef(SearchIcon),
    }
  },
  methods: {
    findTags() {
      this.$emit('findTags', this.searchQuery)
    },
    onTagClick(tag: string) {
      this.$emit('onTagClick', tag)
    }
  }
})
</script>

<style scoped>

</style>
