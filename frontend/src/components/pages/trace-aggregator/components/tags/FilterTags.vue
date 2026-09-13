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
            @click="onTypeClick(type)"
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
            @click="onTagClick(tag)"
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
            @click="onStatusClick(status)"
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
        * filtering only by period and services
      </el-text>
    </template>
    <el-row style="min-height: 80vh">
      <el-col :span="8">
        <FilterTagsSection
            title="Types"
            tagType="success"
            :tags="types"
            :selectedTags="traceAggregatorStore.payload.types!"
            :recentTags="traceAggregatorTagsStore.recentSelections.types"
            :loading="traceAggregatorTagsStore.typesLoading"
            @findTags="findTypes"
            @onTagClick="onTypeClick"
        />
      </el-col>
      <el-col :span="8">
        <FilterTagsSection
            title="Tags (by first 100000)"
            tagType="warning"
            :tags="tags"
            :selectedTags="traceAggregatorStore.payload.tags!"
            :recentTags="traceAggregatorTagsStore.recentSelections.tags"
            :loading="traceAggregatorTagsStore.tagsLoading"
            @findTags="findTags"
            @onTagClick="onTagClick"
        />
      </el-col>
      <el-col :span="8">
        <FilterTagsSection
            title="Statuses"
            tagType="primary"
            :tags="statuses"
            :selectedTags="traceAggregatorStore.payload.statuses!"
            :recentTags="traceAggregatorTagsStore.recentSelections.statuses"
            :loading="traceAggregatorTagsStore.statusesLoading"
            @findTags="findStatuses"
            @onTagClick="onStatusClick"
        />
      </el-col>
    </el-row>
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent, shallowRef} from "vue";
import {Plus as TagAddIcon, Search as SearchIcon} from '@element-plus/icons-vue'
import {TraceTag, useTraceAggregatorTagsStore} from "./store/traceAggregatorTagsStore.ts";
import {useTraceAggregatorStore} from "../traces/store/traceAggregatorStore.ts";
import FilterTagsSection from "./FilterTagsSection.vue";

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
    types() {
      const result: TraceTag[] = [];

      this.traceAggregatorStore.payload.types?.forEach(
          (selectedItem: string) => {
            const exists = this.traceAggregatorTagsStore.types.find(
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

      this.traceAggregatorTagsStore.types?.forEach(
          (item: TraceTag) => {
            result.push(item)
          }
      )

      return result
    },
    tags() {
      const result: TraceTag[] = [];

      this.traceAggregatorStore.payload.tags?.forEach(
          (selectedItem: string) => {
            const exists = this.traceAggregatorTagsStore.tags.find(
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

      this.traceAggregatorTagsStore.tags?.forEach(
          (item: TraceTag) => {
            result.push(item)
          }
      )

      return result
    },
    statuses() {
      const result: TraceTag[] = [];

      this.traceAggregatorStore.payload.statuses?.forEach(
          (selectedItem: string) => {
            const exists = this.traceAggregatorTagsStore.statuses.find(
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

      this.traceAggregatorTagsStore.statuses?.forEach(
          (item: TraceTag) => {
            result.push(item)
          }
      )

      return result
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
    findTypes(text: string) {
      this.traceAggregatorStore.prepareCommonPayloadData()

      this.traceAggregatorTagsStore.typesPayload = {
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

      this.traceAggregatorTagsStore.findTypes()
    },
    findTags(text: string) {
      this.traceAggregatorStore.prepareCommonPayloadData()

      this.traceAggregatorTagsStore.tagsPayload = {
        text: text,
        service_ids: this.traceAggregatorStore.payload.service_ids,
        logging_from_preset: this.traceAggregatorStore.payload.logging_from_preset,
        logging_from: this.traceAggregatorStore.payload.logging_from,
        logging_to: this.traceAggregatorStore.payload.logging_to,
        types: this.traceAggregatorStore.payload.types,
        duration_from: this.traceAggregatorStore.payload.duration_from,
        duration_to: this.traceAggregatorStore.payload.duration_to,
        memory_from: this.traceAggregatorStore.payload.memory_from,
        memory_to: this.traceAggregatorStore.payload.memory_to,
        cpu_from: this.traceAggregatorStore.payload.cpu_from,
        cpu_to: this.traceAggregatorStore.payload.cpu_to,
        data: this.traceAggregatorStore.payload.data,
        has_profiling: this.traceAggregatorStore.payload.has_profiling,
      }

      this.traceAggregatorTagsStore.findTags()
    },
    findStatuses(text: string) {
      this.traceAggregatorStore.prepareCommonPayloadData()

      this.traceAggregatorTagsStore.statusesPayload = {
        text: text,
        service_ids: this.traceAggregatorStore.payload.service_ids,
        logging_from_preset: this.traceAggregatorStore.payload.logging_from_preset,
        logging_from: this.traceAggregatorStore.payload.logging_from,
        logging_to: this.traceAggregatorStore.payload.logging_to,
        types: this.traceAggregatorStore.payload.types,
        tags: this.traceAggregatorStore.payload.tags,
        duration_from: this.traceAggregatorStore.payload.duration_from,
        duration_to: this.traceAggregatorStore.payload.duration_to,
        memory_from: this.traceAggregatorStore.payload.memory_from,
        memory_to: this.traceAggregatorStore.payload.memory_to,
        cpu_from: this.traceAggregatorStore.payload.cpu_from,
        cpu_to: this.traceAggregatorStore.payload.cpu_to,
        data: this.traceAggregatorStore.payload.data,
        has_profiling: this.traceAggregatorStore.payload.has_profiling,
      }

      this.traceAggregatorTagsStore.findStatuses()
    },
    onTypeClick(type: string) {
      const wasSelected = this.traceAggregatorStore.payload.types?.includes(type) ?? false

      this.traceAggregatorStore.addOrDeleteType(type)

      if (!wasSelected) {
        this.traceAggregatorTagsStore.addRecentSelection('types', type)
      }
    },
    onTagClick(tag: string) {
      const wasSelected = this.traceAggregatorStore.payload.tags?.includes(tag) ?? false

      this.traceAggregatorStore.addOrDeleteTag(tag)

      if (!wasSelected) {
        this.traceAggregatorTagsStore.addRecentSelection('tags', tag)
      }
    },
    onStatusClick(status: string) {
      const wasSelected = this.traceAggregatorStore.payload.statuses?.includes(status) ?? false

      this.traceAggregatorStore.addOrDeleteStatus(status)

      if (!wasSelected) {
        this.traceAggregatorTagsStore.addRecentSelection('statuses', status)
      }
    },
  },
})
</script>

<style scoped>

</style>
