<template>
  <el-form :inline="true">
    <el-form-item label="Types:">
      <el-check-tag
          v-for="type in traceStore.state.payload.types"
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
          v-for="tag in traceStore.state.payload.tags"
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
          v-for="status in traceStore.state.payload.statuses"
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
      :style="storeGraph.state.showGraph ? {opacity: .9} : {}"
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
            :selectedTags="traceStore.state.payload.types"
            :loading="tagsStore.state.typesLoading"
            @findTags="findTypes"
            @onTagClick="onTypeClick"
        />
      </el-col>
      <el-col :span="8">
        <FilterTagsSection
            title="Tags (by first 100000)"
            tagType="warning"
            :tags="tags"
            :selectedTags="traceStore.state.payload.tags"
            :loading="tagsStore.state.tagsLoading"
            @findTags="findTags"
            @onTagClick="onTagClick"
        />
      </el-col>
      <el-col :span="8">
        <FilterTagsSection
            title="Statuses"
            tagType="primary"
            :tags="statuses"
            :selectedTags="traceStore.state.payload.statuses"
            :loading="tagsStore.state.statusesLoading"
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
import {TraceTag, useTraceAggregatorTagsStore} from "../../../../store/traceAggregatorTagsStore.ts";
import {useTraceAggregatorStore} from "../../../../store/traceAggregatorStore.ts";
import FilterTagsSection from "./FilterTagsSection.vue";
import {useTraceAggregatorGraphStore} from "../../../../store/traceAggregatorGraphStore.ts";

export default defineComponent({
  components: {FilterTagsSection},
  data() {
    return {
      dialogVisible: false,
      TagAddIcon: shallowRef(TagAddIcon),
      SearchIcon: shallowRef(SearchIcon),
      traceStore: useTraceAggregatorStore(),
      tagsStore: useTraceAggregatorTagsStore(),
      storeGraph: useTraceAggregatorGraphStore(),
    }
  },
  methods: {
    findTypes(text: string) {
      this.traceStore.dispatch('prepareCommonPayloadData')

      this.tagsStore.state.typesPayload = {
        text: text,
        service_ids: this.traceStore.state.payload.service_ids,
        logging_from: this.traceStore.state.payload.logging_from,
        logging_to: this.traceStore.state.payload.logging_to,
        duration_from: this.traceStore.state.payload.duration_from,
        duration_to: this.traceStore.state.payload.duration_to,
        memory_from: this.traceStore.state.payload.memory_from,
        memory_to: this.traceStore.state.payload.memory_to,
        cpu_from: this.traceStore.state.payload.cpu_from,
        cpu_to: this.traceStore.state.payload.cpu_to,
        data: this.traceStore.state.payload.data,
        has_profiling: this.traceStore.state.payload.has_profiling,
      }

      this.tagsStore.state.typesPayload.text = text

      this.tagsStore.dispatch('findTypes')
    },
    findTags(text: string) {
      this.traceStore.dispatch('prepareCommonPayloadData')

      this.tagsStore.state.tagsPayload = {
        text: text,
        service_ids: this.traceStore.state.payload.service_ids,
        logging_from: this.traceStore.state.payload.logging_from,
        logging_to: this.traceStore.state.payload.logging_to,
        types: this.traceStore.state.payload.types,
        duration_from: this.traceStore.state.payload.duration_from,
        duration_to: this.traceStore.state.payload.duration_to,
        memory_from: this.traceStore.state.payload.memory_from,
        memory_to: this.traceStore.state.payload.memory_to,
        cpu_from: this.traceStore.state.payload.cpu_from,
        cpu_to: this.traceStore.state.payload.cpu_to,
        data: this.traceStore.state.payload.data,
        has_profiling: this.traceStore.state.payload.has_profiling,
      }

      this.tagsStore.dispatch('findTags')
    },
    findStatuses(text: string) {
      this.traceStore.dispatch('prepareCommonPayloadData')

      this.tagsStore.state.statusesPayload = {
        text: text,
        service_ids: this.traceStore.state.payload.service_ids,
        logging_from: this.traceStore.state.payload.logging_from,
        logging_to: this.traceStore.state.payload.logging_to,
        types: this.traceStore.state.payload.types,
        tags: this.traceStore.state.payload.tags,
        duration_from: this.traceStore.state.payload.duration_from,
        duration_to: this.traceStore.state.payload.duration_to,
        memory_from: this.traceStore.state.payload.memory_from,
        memory_to: this.traceStore.state.payload.memory_to,
        cpu_from: this.traceStore.state.payload.cpu_from,
        cpu_to: this.traceStore.state.payload.cpu_to,
        data: this.traceStore.state.payload.data,
        has_profiling: this.traceStore.state.payload.has_profiling,
      }

      this.tagsStore.dispatch('findStatuses')
    },
    onTypeClick(type: string) {
      this.traceStore.dispatch('addOrDeleteType', type)
    },
    onTagClick(tag: string) {
      this.traceStore.dispatch('addOrDeleteTag', tag)
    },
    onStatusClick(status: string) {
      this.traceStore.dispatch('addOrDeleteStatus', status)
    },
  },

  computed: {
    types() {
      const result: TraceTag[] = [];

      this.traceStore.state.payload.types?.forEach(
          selectedItem => {
            const exists = this.tagsStore.state.types.find(
                tag => {
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

      this.tagsStore.state.types?.forEach(
          item => {
            result.push(item)
          }
      )

      return result
    },
    tags() {
      const result: TraceTag[] = [];

      this.traceStore.state.payload.tags?.forEach(
          selectedItem => {
            const exists = this.tagsStore.state.tags.find(
                tag => {
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

      this.tagsStore.state.tags?.forEach(
          item => {
            result.push(item)
          }
      )

      return result
    },
    statuses() {
      const result: TraceTag[] = [];

      this.traceStore.state.payload.statuses?.forEach(
          selectedItem => {
            const exists = this.tagsStore.state.statuses.find(
                tag => {
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

      this.tagsStore.state.statuses?.forEach(
          item => {
            result.push(item)
          }
      )

      return result
    },
  }
})
</script>

<style scoped>

</style>
