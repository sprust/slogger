<template>
  <TraceDetailView
      :trace="trace"
      :search-query="searchStore.query"
      :search-in-values="searchStore.inValues"
      :custom-field-names="customFieldNames"
      @update:search-query="(value: string) => searchStore.query = value"
      @update:search-in-values="(value: boolean) => searchStore.inValues = value"
  >
    <template #actions>
      <slot name="actions"/>
    </template>
  </TraceDetailView>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {TraceAggregatorDetail} from "./store/traceAggregatorDataStore.ts";
import {useTraceAggregatorDataSearchStore} from "./store/traceAggregatorDataSearchStore.ts";
import TraceDetailView from "./TraceDetailView.vue";
import {TraceAggregatorCustomField, useTraceAggregatorStore} from "../traces/store/traceAggregatorStore.ts";

export default defineComponent({
  components: {TraceDetailView},

  props: {
    trace: {
      type: Object as PropType<TraceAggregatorDetail>,
      required: true,
    },
  },
  computed: {
    searchStore() {
      return useTraceAggregatorDataSearchStore()
    },
    customFieldNames(): Array<string> {
      return useTraceAggregatorStore().customFields.map(
          (customField: TraceAggregatorCustomField) => customField.field
      )
    },
  },
})
</script>
