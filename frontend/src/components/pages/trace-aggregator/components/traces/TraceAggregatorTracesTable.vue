<template>
  <TraceTracesTableView
      :items="items"
      :selected-types="payload.types ?? []"
      :selected-tags="payload.tags ?? []"
      :selected-statuses="payload.statuses ?? []"
      :highlighted-trace-id="payload.trace_id ?? null"
      :data-fields="payload.data?.fields ?? []"
      :data-items="traceAggregatorDataStore.dataItems"
      :search-query="searchStore.query"
      :search-in-values="searchStore.inValues"
      :custom-field-names="customFieldNames"
      @expand="dataExpandChange"
      @type-click="(type: string) => $emit('onTraceTypeClick', type)"
      @tag-click="(tag: string) => $emit('onTraceTagClick', tag)"
      @status-click="(status: string) => $emit('onTraceStatusClick', status)"
      @custom-field-click="onCustomFieldClick"
      @tree-parent="onClickTraceIdTreeParent"
      @tree-current="onClickTraceIdTreeCurrent"
      @trace-id-filter="onClickTraceIdFilter"
      @profiling="onShowProfiling"
      @update:search-query="(value: string) => searchStore.query = value"
      @update:search-in-values="(value: boolean) => searchStore.inValues = value"
  />
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
import {
  TraceAggregatorCustomField,
  TraceAggregatorCustomFieldParameter,
  TraceAggregatorItems,
  TraceAggregatorPayload,
  useTraceAggregatorStore,
} from "./store/traceAggregatorStore.ts";
import TraceTracesTableView from "./TraceTracesTableView.vue";
import {traceAggregatorTabs, useTraceAggregatorTabsStore} from "../../store/traceAggregatorTabsStore.ts";
import {useTraceAggregatorTreeStore} from "../tree/store/traceAggregatorTreeStore.ts";
import {useTraceAggregatorDataStore} from "../trace/store/traceAggregatorDataStore.ts";
import {useTraceAggregatorDataSearchStore} from "../trace/store/traceAggregatorDataSearchStore.ts";
import {useTraceAggregatorProfilingStore} from "../profiling/store/traceAggregatorProfilingStore.ts";

export default defineComponent({
  components: {TraceTracesTableView},

  emits: ["onTraceTypeClick", "onTraceTagClick", "onTraceStatusClick", "onCustomFieldClick"],

  props: {
    payload: {
      type: Object as PropType<TraceAggregatorPayload>,
      required: true,
    },
    items: {
      type: Array as PropType<TraceAggregatorItems>,
      required: true,
    },
  },

  computed: {
    traceAggregatorDataStore() {
      return useTraceAggregatorDataStore()
    },
    traceAggregatorTreeStore() {
      return useTraceAggregatorTreeStore()
    },
    traceAggregatorTabsStore() {
      return useTraceAggregatorTabsStore()
    },
    traceAggregatorProfilingStore() {
      return useTraceAggregatorProfilingStore()
    },
    searchStore() {
      return useTraceAggregatorDataSearchStore()
    },
    customFieldNames(): Array<string> {
      return useTraceAggregatorStore().customFields.map(
          (customField: TraceAggregatorCustomField) => customField.field
      )
    },
  },

  methods: {
    dataExpandChange(traceId: string) {
      if (this.isTraceDataLoaded(traceId)) {
        return
      }

      this.traceAggregatorDataStore.findTraceData(traceId)
    },
    isTraceDataLoaded(traceId: string): boolean {
      return !!this.traceAggregatorDataStore.dataItems[traceId]?.loaded
    },
    onCustomFieldClick(parameters: TraceAggregatorCustomFieldParameter) {
      this.$emit("onCustomFieldClick", parameters)
    },
    onClickTraceIdTreeParent(traceId: string) {
      this.traceAggregatorTreeStore.initTreeParent(traceId)

      this.traceAggregatorTabsStore.setCurrentTab(traceAggregatorTabs.tree)
    },
    onClickTraceIdTreeCurrent(traceId: string) {
      this.traceAggregatorTreeStore.initTreeCurrent(traceId)

      this.traceAggregatorTabsStore.setCurrentTab(traceAggregatorTabs.tree)
    },
    onShowProfiling(traceId: string) {
      this.traceAggregatorProfilingStore.findProfiling(traceId)

      this.traceAggregatorTabsStore.setCurrentTab(traceAggregatorTabs.profiling)
    },
    onClickTraceIdFilter(traceId: string) {
      if (traceId === this.payload.trace_id) {
        this.payload.trace_id = ''

        return
      }

      this.payload.trace_id = traceId
    },
  },
})
</script>
