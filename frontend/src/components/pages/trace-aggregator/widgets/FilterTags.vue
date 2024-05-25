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
      @open="onDialogOpen"
      :style="storeGraph.state.showGraph ? {opacity: .9} : {}"
  >
    <el-row style="min-height: 80vh">
      <el-col :span="8">
        <FilterTagsSection
            title="Types"
            tagType="success"
            :tags="tagsStore.state.types"
            :selectedTags="traceStore.state.payload.types"
            @findTags="findTypes"
            @onTagClick="onTypeClick"
        />
      </el-col>
      <el-col :span="8">
        <FilterTagsSection
            title="Tags"
            tagType="warning"
            :tags="tagsStore.state.tags"
            :selectedTags="traceStore.state.payload.tags"
            @findTags="findTags"
            @onTagClick="onTagClick"
        />
      </el-col>
      <el-col :span="8">
        <FilterTagsSection
            title="Statuses"
            tagType="primary"
            :tags="tagsStore.state.statuses"
            :selectedTags="traceStore.state.payload.statuses"
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
import {useTraceAggregatorTagsStore} from "../../../../store/traceAggregatorTagsStore.ts";
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
    onDialogOpen() {
      this.findTypes(this.tagsStore.state.typesPayload.text)
      this.findTags(this.tagsStore.state.tagsPayload.text)
      this.findStatuses(this.tagsStore.state.statusesPayload.text)
    },
    findTypes(text: string) {
      this.traceStore.dispatch('prepareCommonPayloadData')

      this.tagsStore.state.typesPayload = {
        text: text,
        service_ids: this.traceStore.state.payload.service_ids,
        logging_from: this.traceStore.state.payload.logging_from,
        logging_to: this.traceStore.state.payload.logging_to,
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
        data: this.traceStore.state.payload.data,
        has_profiling: this.traceStore.state.payload.has_profiling,
      }

      this.tagsStore.dispatch('findStatuses')
    },
    onTypeClick(type: string) {
      this.traceStore.dispatch('addOrDeleteType', type)

      this.findTags(this.tagsStore.state.tagsPayload.text)
      this.findStatuses(this.tagsStore.state.statusesPayload.text)
    },
    onTagClick(tag: string) {
      this.traceStore.dispatch('addOrDeleteTag', tag)

      this.findStatuses(this.tagsStore.state.statusesPayload.text)
    },
    onStatusClick(status: string) {
      this.traceStore.dispatch('addOrDeleteStatus', status)
    },
  },
})
</script>

<style scoped>

</style>
