<template>
  <el-form @submit.prevent="findTags">
    <el-form-item :label="title">
    </el-form-item>
    <el-form-item>
      <div class="search-actions"  style="margin: 3px">
        <el-space>
          <el-dropdown
              trigger="click"
              :disabled="recentTags.length === 0"
              @command="onHistorySelect"
          >
            <el-button
                :icon="ArrowDownIcon"
                :disabled="recentTags.length === 0"
                style="width: 30px"
            />
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item
                    v-for="tag in recentTags"
                    :key="tag"
                    :command="tag"
                >
                  {{ tag }}
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </el-space>
        <el-button
            :icon="TagAddIcon"
            @click="onAddTagClick"
            :disabled="!searchQuery"
        />

        <el-input v-model="searchQuery" clearable class="search-input"/>

        <el-button :icon="SearchIcon" @click="findTags" :loading="loading.loading"/>
      </div>
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
import {ArrowDown, Plus as TagAddIcon, Search as SearchIcon} from '@element-plus/icons-vue'
import {TagLoading, TraceTag} from "./store/traceAggregatorTagsStore.ts";

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
    recentTags: {
      type: Array as PropType<string[]>,
      required: true,
    },
  },
  data() {
    return {
      searchQuery: '',
      ArrowDownIcon: shallowRef(ArrowDown),
      TagAddIcon: shallowRef(TagAddIcon),
      SearchIcon: shallowRef(SearchIcon),
    }
  },
  methods: {
    onAddTagClick() {
      if (!this.searchQuery) {
        return
      }

      this.$emit('onTagClick', this.searchQuery)

      this.searchQuery = ''
    },
    findTags() {
      this.$emit('findTags', this.searchQuery)
    },
    onHistorySelect(tag: string) {
      this.$emit('onTagClick', tag)
    },
    onTagClick(tag: string) {
      this.$emit('onTagClick', tag)
    }
  }
})
</script>

<style scoped>
.search-actions {
  display: flex;
  align-items: stretch;
  width: 100%;
  gap: 8px;
}

.search-input {
  flex: 1;
}
</style>
