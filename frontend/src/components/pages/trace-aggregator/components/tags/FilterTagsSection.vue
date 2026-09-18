<template>
  <el-form @submit.prevent="findTags">
    <el-form-item>
      <div class="section-title">
        <el-text>{{ title }}</el-text>
        <el-tooltip
            content="Move left, to filter it by less"
            placement="top"
            :disabled="!canMoveLeft"
        >
          <el-button
              link
              :icon="ArrowLeftIcon"
              :disabled="!canMoveLeft"
              @click="$emit('moveLeft')"
          />
        </el-tooltip>
        <el-tooltip
            content="Move right, to filter it by more"
            placement="top"
            :disabled="!canMoveRight"
        >
          <el-button
              link
              :icon="ArrowRightIcon"
              :disabled="!canMoveRight"
              @click="$emit('moveRight')"
          />
        </el-tooltip>
      </div>
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
import {
  ArrowDown,
  ArrowLeft as ArrowLeftIcon,
  ArrowRight as ArrowRightIcon,
  Plus as TagAddIcon,
  Search as SearchIcon
} from '@element-plus/icons-vue'
import {TagLoading, TraceTag} from "./store/traceAggregatorTagsStore.ts";

export default defineComponent({
  emits: ['findTags', 'onTagClick', 'moveLeft', 'moveRight'],
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
    canMoveLeft: {
      type: Boolean,
      required: true,
    },
    canMoveRight: {
      type: Boolean,
      required: true,
    },
  },
  data() {
    return {
      searchQuery: '',
      ArrowDownIcon: shallowRef(ArrowDown),
      ArrowLeftIcon: shallowRef(ArrowLeftIcon),
      ArrowRightIcon: shallowRef(ArrowRightIcon),
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
.section-title {
  display: flex;
  align-items: center;
  gap: 4px;
}

/* element-plus spaces neighbouring buttons apart; the flex gap already does that */
.section-title :deep(.el-button + .el-button) {
  margin-left: 0;
}

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
