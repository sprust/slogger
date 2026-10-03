<template>
  <el-table
      :data="items"
      table-layout="auto"
      @expandChange="(trace: TraceAggregatorItem) => $emit('expand', trace.trace.trace_id)"
      border
  >
    <el-table-column type="expand">
      <template #default="props">
        <el-progress
            v-if="!dataItems[props.row.trace.trace_id]?.loaded"
            status="success"
            :text-inside="true"
            :percentage="100"
            :indeterminate="true"
            :duration="1"
        />
        <div v-else :style="{width: expandedWidth}">
          <TraceAggregatorTraceDataNode
              :data="dataItems[props.row.trace.trace_id].data"
              :showCustomButton="true"
              :search-query="searchQuery"
              :search-in-values="searchInValues"
              :custom-field-names="customFieldNames"
              @onCustomFieldClick="(parameters: TraceAggregatorCustomFieldParameter) => $emit('custom-field-click', parameters)"
              @update:search-query="(value: string) => $emit('update:searchQuery', value)"
              @update:search-in-values="(value: boolean) => $emit('update:searchInValues', value)"
          />
        </div>
      </template>
    </el-table-column>

    <el-table-column prop="trace.service" label="Service">
      <template #default="scope">
        <TraceService :name="scope.row.trace.service?.name"/>
      </template>
    </el-table-column>

    <el-table-column label="Logged at / Ids">
      <template #default="props">
        <el-row style="font-weight: bold">
          <el-space>
            <el-text>
              {{ props.row.trace.logged_at }}
            </el-text>
            <el-button
                v-if="props.row.trace.has_profiling"
                type="info"
                @click="$emit('profiling', props.row.trace.trace_id)"
                link
            >
              profiling
            </el-button>
          </el-space>
        </el-row>
        <el-row>
          <TraceId
              title="id"
              :traceId="props.row.trace.trace_id"
              @onClickTraceIdTreeParent="(traceId: string) => $emit('tree-parent', traceId)"
              @onClickTraceIdTreeCurrent="(traceId: string) => $emit('tree-current', traceId)"
              @onClickTraceIdFilter="(traceId: string) => $emit('trace-id-filter', traceId)"
              :style="highlightedTraceId === props.row.trace.trace_id ? {'color': 'green'} : {}"
          />
        </el-row>
        <el-row v-if="props.row.trace.parent_trace_id">
          <TraceId
              title="parent id"
              :trace-id="props.row.trace.parent_trace_id"
              @onClickTraceIdTreeParent="(traceId: string) => $emit('tree-parent', traceId)"
              @onClickTraceIdTreeCurrent="(traceId: string) => $emit('tree-current', traceId)"
              @onClickTraceIdFilter="(traceId: string) => $emit('trace-id-filter', traceId)"
              :style="!!props.row.trace.parent_trace_id && highlightedTraceId === props.row.trace.parent_trace_id ? {'color': 'green'} : {}"
          />
        </el-row>
      </template>
    </el-table-column>

    <el-table-column prop="trace.type" label="Type">
      <template #default="scope">
        <el-tooltip
            :disabled="scope.row.trace.type.length <= 45"
            :content="scope.row.trace.type"
            placement="top"
        >
          <el-check-tag
              type="success"
              style="white-space: pre-line"
              :checked="selectedTypes.indexOf(scope.row.trace.type) !== -1"
              @click="$emit('type-click', scope.row.trace.type)"
          >
            {{ formatLabel(scope.row.trace.type) }}
          </el-check-tag>
        </el-tooltip>
      </template>
    </el-table-column>

    <el-table-column label="Tags">
      <template #default="scope">
        <el-tooltip
            v-for="tag in scope.row.trace.tags"
            :disabled="tag.length <= 45"
            :content="tag"
            placement="top"
        >
          <el-check-tag
              type="warning"
              style="white-space: pre-line"
              :checked="selectedTags.indexOf(tag) !== -1"
              @click="$emit('tag-click', tag)"
          >
            {{ formatLabel(tag) }}
          </el-check-tag>
        </el-tooltip>
      </template>
    </el-table-column>

    <el-table-column label="Status">
      <template #default="scope">
        <el-tooltip
            :disabled="scope.row.trace.status.length <= 45"
            :content="scope.row.trace.status"
            placement="top"
        >
          <el-check-tag
              type="primary"
              style="white-space: pre-line"
              :checked="selectedStatuses.indexOf(scope.row.trace.status) !== -1"
              @click="$emit('status-click', scope.row.trace.status)"
          >
            {{ formatLabel(scope.row.trace.status) }}
          </el-check-tag>
        </el-tooltip>
      </template>
    </el-table-column>

    <el-table-column prop="trace.type" label="Metrics">
      <template #default="scope">
        <TraceItemMetrics
            :duration="scope.row.trace.duration"
            :memory="scope.row.trace.memory"
            :cpu="scope.row.trace.cpu"
        />
      </template>
    </el-table-column>

    <el-table-column
        v-if="dataFields.length"
        v-for="customField in dataFields"
        :key="customField"
        :label="customField"
    >
      <template #default="scope">
        <div
            v-for="customFieldItem in scope.row.trace.additional_fields.filter(
                (valueItem: TraceAggregatorAdditionalField) => valueItem.key === customField
            )"
            :key="customFieldItem.key"
        >
          <div v-for="value in customFieldItem.values">
            {{ value }}
          </div>
        </div>
      </template>
    </el-table-column>
  </el-table>
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
import type {
  TraceAggregatorAdditionalField,
  TraceAggregatorCustomFieldParameter,
  TraceAggregatorItem,
  TraceAggregatorItems,
} from "./store/traceAggregatorStore.ts";
import type {TraceAggregatorDetailData} from "../trace/store/traceAggregatorDataStore.ts";
import TraceAggregatorTraceDataNode from "../trace/TraceAggregatorTraceDataNode.vue";
import TraceItemMetrics from "./TraceItemMetrics.vue";
import TraceService from "../services/TraceService.vue";
import TraceId from "../trace/TraceId.vue";

export interface TraceTableDataItem {
  loaded: boolean,
  data: TraceAggregatorDetailData,
}

export default defineComponent({
  components: {TraceId, TraceService, TraceAggregatorTraceDataNode, TraceItemMetrics},

  emits: [
    'expand',
    'type-click',
    'tag-click',
    'status-click',
    'custom-field-click',
    'tree-parent',
    'tree-current',
    'trace-id-filter',
    'profiling',
    'update:searchQuery',
    'update:searchInValues',
  ],

  props: {
    items: {
      type: Array as PropType<TraceAggregatorItems>,
      required: true,
    },
    selectedTypes: {
      type: Array as PropType<Array<string>>,
      default: () => [],
    },
    selectedTags: {
      type: Array as PropType<Array<string>>,
      default: () => [],
    },
    selectedStatuses: {
      type: Array as PropType<Array<string>>,
      default: () => [],
    },
    highlightedTraceId: {
      type: String as PropType<string | null>,
      default: null,
    },
    dataFields: {
      type: Array as PropType<Array<string>>,
      default: () => [],
    },
    dataItems: {
      type: Object as PropType<Record<string, TraceTableDataItem>>,
      required: true,
    },
    searchQuery: {
      type: String,
      required: true,
    },
    searchInValues: {
      type: Boolean,
      required: true,
    },
    customFieldNames: {
      type: Array as PropType<Array<string>>,
      default: () => [],
    },
    expandedWidth: {
      type: String,
      default: '90vw',
    },
  },

  methods: {
    formatLabel(value: string, limit: number = 45): string {
      if (value.length <= limit) {
        return value
      }

      const rest: string = value.slice(limit)
      const secondLine: string = rest.length > limit ? rest.slice(0, limit) + '…' : rest

      return value.slice(0, limit) + '\n' + secondLine
    },
  },
})
</script>

<style scoped>
</style>
