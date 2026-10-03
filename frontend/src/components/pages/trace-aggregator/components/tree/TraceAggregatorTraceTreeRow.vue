<script lang="ts">

import {defineComponent, PropType} from "vue";
import TraceTreeRowView from "./TraceTreeRowView.vue";
import {TraceAggregatorTreeRow, TraceTreeNode, useTraceAggregatorTreeStore} from "./store/traceAggregatorTreeStore.ts";
import {useTraceAggregatorServicesStore} from "../services/store/traceAggregatorServicesStore.ts";

export default defineComponent({
  components: {TraceTreeRowView},

  props: {
    row: {
      type: Object as PropType<TraceTreeNode>,
      required: true
    },
  },

  computed: {
    traceAggregatorTreeStore() {
      return useTraceAggregatorTreeStore()
    },
    traceAggregatorServicesStore() {
      return useTraceAggregatorServicesStore()
    },
  },

  methods: {
    isSelected(trace: TraceAggregatorTreeRow): boolean {
      return trace.trace_id === this.traceAggregatorTreeStore.selectedTrace.trace_id
    },
    isHighlighted(trace: TraceAggregatorTreeRow): boolean {
      return trace.trace_id === this.traceAggregatorTreeStore.parameters.trace_id
    },
    makeIndicatorWidthPercent(trace: TraceAggregatorTreeRow): number {
      let percent = 0

      if (trace.duration && this.traceAggregatorTreeStore.traceIndicatingIds.indexOf(trace.trace_id) !== -1) {
        percent = (trace.duration / this.traceAggregatorTreeStore.traceTotalIndicatorsNumber) * 50
      }

      return percent
    },
    onClickOnRow(treeNode: TraceAggregatorTreeRow) {
      this.traceAggregatorTreeStore.findData(treeNode.trace_id)
    },
    getServiceName(treeNode: TraceAggregatorTreeRow) {
      // The tree's own figures come with the filters and can take seconds on a large
      // tree; the plain list of services is there long before, and the name is the same.
      return this.traceAggregatorTreeStore.servicesMap[treeNode.service_id]?.name
          ?? this.traceAggregatorServicesStore.byId[treeNode.service_id]?.name
          ?? 'NO LOAD'
    },
    isServiceIdSelected(serviceId: number): boolean {
      return this.traceAggregatorTreeStore.selectedTraceServiceIds.indexOf(serviceId) != -1
    },
    isTypeSelected(item: string): boolean {
      return this.traceAggregatorTreeStore.selectedTraceTypes.indexOf(item) != -1
    },
    isStatusSelected(item: string): boolean {
      return this.traceAggregatorTreeStore.selectedTraceStatuses.indexOf(item) != -1
    },
    findByRow() {
      this.traceAggregatorTreeStore.initTreeByRow(this.row)
    },
    showJson() {
      this.traceAggregatorTreeStore.showBranchJson(this.row)
    },
    indicateByRow() {
      this.traceAggregatorTreeStore.fillTreeIndicatorsByRow(this.row)
    },
    toggleCollapse() {
      this.traceAggregatorTreeStore.toggleCollapse(this.row)
    },
    loadMore() {
      if (this.row.loadMoreOf) {
        this.traceAggregatorTreeStore.loadLazyChildren(this.row.loadMoreOf)
      }
    },
  },
})
</script>

<template>
  <TraceTreeRowView
      v-if="row.loadMoreOf"
      :row="row"
      service-name=""
      @load-more="loadMore"
  />
  <TraceTreeRowView
      v-else
      :row="row"
      :service-name="getServiceName(row.primary)"
      :selected="isSelected(row.primary)"
      :highlighted="isHighlighted(row.primary)"
      :indicator-width-percent="makeIndicatorWidthPercent(row.primary)"
      :service-selected="isServiceIdSelected(row.primary.service_id)"
      :type-selected="isTypeSelected(row.primary.type)"
      :status-selected="isStatusSelected(row.primary.status)"
      :selected-tags="traceAggregatorTreeStore.selectedTraceTags"
      @select="onClickOnRow(row.primary)"
      @toggle-collapse="toggleCollapse"
      @find-tree="findByRow"
      @show-json="showJson"
      @indicate="indicateByRow"
  />
</template>
