<template>
  <el-form :inline="true">
    <el-form-item label="Types:">
      <el-check-tag
          v-for="type in traceAggregatorStore.payload.types"
          :key="type"
          type="success"
          :checked="true"
          @click="onTypeClick(type)"
      >
        {{ type }}
      </el-check-tag>
    </el-form-item>
    <el-form-item label="Tags:">
      <el-check-tag
          v-for="tag in traceAggregatorStore.payload.tags"
          :key="tag"
          type="warning"
          :checked="true"
          @click="onTagClick(tag)"
      >
        {{ tag }}
      </el-check-tag>
    </el-form-item>
    <el-form-item label="Statuses:">
      <el-check-tag
          v-for="status in traceAggregatorStore.payload.statuses"
          :key="status"
          type="primary"
          :checked="true"
          @click="onStatusClick(status)"
      >
        {{ status }}
      </el-check-tag>
    </el-form-item>
    <el-form-item>
      <el-button :icon="TagAddIcon" @click="dialogVisible = true"/>
    </el-form-item>
  </el-form>

  <el-dialog
      v-model="dialogVisible"
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
      dialogVisible: false,
      TagAddIcon: shallowRef(TagAddIcon),
      SearchIcon: shallowRef(SearchIcon),
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
    findTypes(text: string) {
      this.traceAggregatorStore.prepareCommonPayloadData()

      this.traceAggregatorTagsStore.typesPayload = {
        text: text,
        service_ids: this.traceAggregatorStore.payload.service_ids,
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
      this.traceAggregatorStore.addOrDeleteType(type)
    },
    onTagClick(tag: string) {
      this.traceAggregatorStore.addOrDeleteTag(tag)
    },
    onStatusClick(status: string) {
      this.traceAggregatorStore.addOrDeleteStatus(status)
    },
  },
})
</script>

<style scoped>

</style>
