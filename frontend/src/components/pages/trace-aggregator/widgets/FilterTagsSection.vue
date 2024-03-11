<template>
  <el-form @submit.prevent="findTags">
    <el-form-item :label="title">
    </el-form-item>
    <el-form-item>
      <el-input v-model="searchQuery" clearable>
        <template #append>
          <el-button :icon="SearchIcon" @click="findTags"/>
        </template>
      </el-input>
    </el-form-item>
    <el-form-item>
      <el-row>
        <el-check-tag
            v-for="tag in tags"
            :key="tag"
            :type="tagType"
            :checked="selectedTags.indexOf(tag) !== -1"
            @click="onTagClick(tag)"
        >
          {{ tag }}
        </el-check-tag>
      </el-row>
    </el-form-item>
  </el-form>
</template>

<script lang="ts">
import {defineComponent, shallowRef} from "vue";
import {Plus as TagAddIcon, Search as SearchIcon} from '@element-plus/icons-vue'

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
      type: Array<string>,
      required: true,
    },
    selectedTags: {
      type: Array<string>,
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
