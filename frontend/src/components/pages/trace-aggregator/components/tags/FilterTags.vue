<template>
  <el-form :inline="true">
    <el-form-item label="Types:">
      <el-tooltip
          v-for="type in visibleOf(traceAggregatorStore.payload.types)"
          :key="type"
          :content="type"
          :disabled="type.length <= maxTagLength"
          placement="top"
      >
        <el-check-tag
            type="success"
            :checked="true"
            @click="onSectionTagClick('types', type)"
        >
          {{ truncate(type) }}
        </el-check-tag>
      </el-tooltip>
      <el-tooltip
          v-if="hiddenOf(traceAggregatorStore.payload.types).length"
          placement="top"
      >
        <template #content>
          <div v-for="hidden in hiddenOf(traceAggregatorStore.payload.types)" :key="hidden">
            {{ hidden }}
          </div>
        </template>
        <el-check-tag
            type="success"
            :checked="true"
            @click="traceAggregatorTagsStore.showDialog = true"
        >
          +{{ hiddenOf(traceAggregatorStore.payload.types).length }}
        </el-check-tag>
      </el-tooltip>
    </el-form-item>
    <el-form-item label="Tags:">
      <el-tooltip
          v-for="tag in visibleOf(traceAggregatorStore.payload.tags)"
          :key="tag"
          :content="tag"
          :disabled="tag.length <= maxTagLength"
          placement="top"
      >
        <el-check-tag
            type="warning"
            :checked="true"
            @click="onSectionTagClick('tags', tag)"
        >
          {{ truncate(tag) }}
        </el-check-tag>
      </el-tooltip>
      <el-tooltip
          v-if="hiddenOf(traceAggregatorStore.payload.tags).length"
          placement="top"
      >
        <template #content>
          <div v-for="hidden in hiddenOf(traceAggregatorStore.payload.tags)" :key="hidden">
            {{ hidden }}
          </div>
        </template>
        <el-check-tag
            type="warning"
            :checked="true"
            @click="traceAggregatorTagsStore.showDialog = true"
        >
          +{{ hiddenOf(traceAggregatorStore.payload.tags).length }}
        </el-check-tag>
      </el-tooltip>
    </el-form-item>
    <el-form-item label="Statuses:">
      <el-tooltip
          v-for="status in visibleOf(traceAggregatorStore.payload.statuses)"
          :key="status"
          :content="status"
          :disabled="status.length <= maxTagLength"
          placement="top"
      >
        <el-check-tag
            type="primary"
            :checked="true"
            @click="onSectionTagClick('statuses', status)"
        >
          {{ truncate(status) }}
        </el-check-tag>
      </el-tooltip>
      <el-tooltip
          v-if="hiddenOf(traceAggregatorStore.payload.statuses).length"
          placement="top"
      >
        <template #content>
          <div v-for="hidden in hiddenOf(traceAggregatorStore.payload.statuses)" :key="hidden">
            {{ hidden }}
          </div>
        </template>
        <el-check-tag
            type="primary"
            :checked="true"
            @click="traceAggregatorTagsStore.showDialog = true"
        >
          +{{ hiddenOf(traceAggregatorStore.payload.statuses).length }}
        </el-check-tag>
      </el-tooltip>
    </el-form-item>
    <el-form-item>
      <el-button :icon="TagAddIcon" @click="traceAggregatorTagsStore.showDialog = true"/>
    </el-form-item>
  </el-form>

  <el-dialog
      v-model="traceAggregatorTagsStore.showDialog"
      width="80%"
      top="10px"
      :append-to-body="true"
      style="opacity: .9"
  >
    <template #header>
      <el-text>
        * every column is filtered by period, services and by what is chosen in the columns on its left
      </el-text>
    </template>
    <el-row style="min-height: 80vh">
      <el-col
          v-for="(section, index) in sections"
          :key="section.key"
          :span="8"
      >
        <FilterTagsSection
            :title="section.title"
            :tagType="section.tagType"
            :tags="section.tags"
            :selectedTags="section.selectedTags"
            :recentTags="traceAggregatorTagsStore.recentSelections[section.key]"
            :loading="section.loading"
            :canMoveLeft="index > 0"
            :canMoveRight="index < sections.length - 1"
            @findTags="(text: string) => findSection(section.key, text)"
            @onTagClick="(tag: string) => onSectionTagClick(section.key, tag)"
            @moveLeft="traceAggregatorTagsStore.moveSection(section.key, -1)"
            @moveRight="traceAggregatorTagsStore.moveSection(section.key, 1)"
        />
      </el-col>
    </el-row>
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent, shallowRef} from "vue";
import {Plus as TagAddIcon, Search as SearchIcon} from '@element-plus/icons-vue'
import {
  TagLoading,
  TraceTag,
  TraceTagHistoryType,
  useTraceAggregatorTagsStore
} from "./store/traceAggregatorTagsStore.ts";
import {TraceAggregatorCommonPayload, useTraceAggregatorStore} from "../traces/store/traceAggregatorStore.ts";
import FilterTagsSection from "./FilterTagsSection.vue";

type FindPayload = TraceAggregatorCommonPayload & {
  text?: string | null,
}

const sectionTitles: Record<TraceTagHistoryType, string> = {
  types: 'Types',
  tags: 'Tags (by first 100000)',
  statuses: 'Statuses',
}

const sectionTagTypes: Record<TraceTagHistoryType, string> = {
  types: 'success',
  tags: 'warning',
  statuses: 'primary',
}

export default defineComponent({
  components: {FilterTagsSection},

  data() {
    return {
      TagAddIcon: shallowRef(TagAddIcon),
      SearchIcon: shallowRef(SearchIcon),
      maxTagLength: 30,
      maxVisibleTags: 2,
    }
  },

  computed: {
    traceAggregatorStore() {
      return useTraceAggregatorStore()
    },
    traceAggregatorTagsStore() {
      return useTraceAggregatorTagsStore()
    },
    sections() {
      return this.traceAggregatorTagsStore.sectionOrder.map(
          (key: TraceTagHistoryType) => {
            return {
              key: key,
              title: sectionTitles[key],
              tagType: sectionTagTypes[key],
              tags: this.foundTagsOf(key),
              selectedTags: this.traceAggregatorStore.payload[key] ?? [],
              loading: this.loadingOf(key),
            }
          }
      )
    },
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
    loadingOf(key: TraceTagHistoryType): TagLoading {
      if (key === 'types') {
        return this.traceAggregatorTagsStore.typesLoading
      }

      if (key === 'tags') {
        return this.traceAggregatorTagsStore.tagsLoading
      }

      return this.traceAggregatorTagsStore.statusesLoading
    },
    // Selected values the last search did not return still have to be shown as checked.
    foundTagsOf(key: TraceTagHistoryType): TraceTag[] {
      const found = this.traceAggregatorTagsStore[key] ?? []

      const result: TraceTag[] = []

      this.traceAggregatorStore.payload[key]?.forEach(
          (selectedItem: string) => {
            const exists = found.find(
                (tag: TraceTag) => {
                  return tag.name === selectedItem
                }
            )

            if (exists) {
              return
            }

            result.push({
              name: selectedItem,
              count: 0
            })
          }
      )

      found.forEach(
          (item: TraceTag) => {
            result.push(item)
          }
      )

      return result
    },
    makeFindPayload(key: TraceTagHistoryType, text: string): FindPayload {
      this.traceAggregatorStore.prepareCommonPayloadData()

      const payload: FindPayload = {
        text: text,
        service_ids: this.traceAggregatorStore.payload.service_ids,
        logging_from_preset: this.traceAggregatorStore.payload.logging_from_preset,
        logging_from: this.traceAggregatorStore.payload.logging_from,
        logging_to: this.traceAggregatorStore.payload.logging_to,
        duration_from: this.traceAggregatorStore.payload.duration_from,
        duration_to: this.traceAggregatorStore.payload.duration_to,
        memory_from: this.traceAggregatorStore.payload.memory_from,
        memory_to: this.traceAggregatorStore.payload.memory_to,
        cpu_from: this.traceAggregatorStore.payload.cpu_from,
        cpu_to: this.traceAggregatorStore.payload.cpu_to,
        data: this.traceAggregatorStore.payload.data,
        has_profiling: this.traceAggregatorStore.payload.has_profiling,
      }

      const order = this.traceAggregatorTagsStore.sectionOrder
      const previous = order.slice(0, order.indexOf(key))

      if (previous.includes('types')) {
        payload.types = this.traceAggregatorStore.payload.types
      }

      if (previous.includes('tags')) {
        payload.tags = this.traceAggregatorStore.payload.tags
      }

      if (previous.includes('statuses')) {
        payload.statuses = this.traceAggregatorStore.payload.statuses
      }

      return payload
    },
    findSection(key: TraceTagHistoryType, text: string) {
      const payload = this.makeFindPayload(key, text)

      if (key === 'types') {
        this.traceAggregatorTagsStore.typesPayload = payload

        this.traceAggregatorTagsStore.findTypes()

        return
      }

      if (key === 'tags') {
        this.traceAggregatorTagsStore.tagsPayload = payload

        this.traceAggregatorTagsStore.findTags()

        return
      }

      this.traceAggregatorTagsStore.statusesPayload = payload

      this.traceAggregatorTagsStore.findStatuses()
    },
    onSectionTagClick(key: TraceTagHistoryType, value: string) {
      const wasSelected = this.traceAggregatorStore.payload[key]?.includes(value) ?? false

      if (key === 'types') {
        this.traceAggregatorStore.addOrDeleteType(value)
      } else if (key === 'tags') {
        this.traceAggregatorStore.addOrDeleteTag(value)
      } else {
        this.traceAggregatorStore.addOrDeleteStatus(value)
      }

      if (!wasSelected) {
        this.traceAggregatorTagsStore.addRecentSelection(key, value)
      }
    },
  },
})
</script>

<style scoped>

</style>
