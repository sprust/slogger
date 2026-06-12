<template>
  <el-table
      :data="items"
      table-layout="auto"
      @expandChange="dataExpandChange"
      border
  >
    <el-table-column type="expand">
      <template #default="props">
        <el-progress
            v-if="!isTraceDataLoaded(props.row.trace.trace_id)"
            status="success"
            :text-inside="true"
            :percentage="100"
            :indeterminate="true"
            :duration="1"
        />
        <div v-else style="width: 90vw">
          <TraceAggregatorTraceDataNode
              :data="traceAggregatorDataStore.dataItems[props.row.trace.trace_id].data"
              :showCustomButton="true"
              @onCustomFieldClick="onCustomFieldClick"
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
                @click="onShowProfiling(props.row.trace.trace_id)"
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
              @onClickTraceIdTreeParent="onClickTraceIdTreeParent"
              @onClickTraceIdTreeCurrent="onClickTraceIdTreeCurrent"
              @onClickTraceIdFilter="onClickTraceIdFilter"
              :style="isTraceInPayload(props.row.trace.trace_id) ? {'color': 'green'} : {}"
          />
        </el-row>
        <el-row v-if="props.row.trace.parent_trace_id">
          <TraceId
              title="parent id"
              :trace-id="props.row.trace.parent_trace_id"
              @onClickTraceIdTreeParent="onClickTraceIdTreeParent"
              @onClickTraceIdTreeCurrent="onClickTraceIdTreeCurrent"
              @onClickTraceIdFilter="onClickTraceIdFilter"
              :style="isTraceInPayload(props.row.trace.parent_trace_id) ? {'color': 'green'} : {}"
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
              :checked="payload.types && payload.types?.indexOf(scope.row.trace.type) !== -1"
              @click="$emit('onTraceTypeClick', scope.row.trace.type)"
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
              :checked="payload.tags && payload.tags?.indexOf(tag) !== -1"
              @click="$emit('onTraceTagClick', tag)"
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
              :checked="payload.statuses && payload.statuses?.indexOf(scope.row.trace.status) !== -1"
              @click="$emit('onTraceStatusClick', scope.row.trace.status)"
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
        v-if="payload.data?.fields?.length"
        v-for="customField in payload.data?.fields"
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
import {
  TraceAggregatorAdditionalField, // not delete
  TraceAggregatorCustomFieldParameter, TraceAggregatorItem,
  TraceAggregatorItems,
  TraceAggregatorPayload,
} from "./store/traceAggregatorStore.ts";
import TraceAggregatorTraceDataNode from "../trace/TraceAggregatorTraceDataNode.vue";
import FilterTags from "../tags/FilterTags.vue";
import TraceItemMetrics from "./TraceItemMetrics.vue";
import {traceAggregatorTabs, useTraceAggregatorTabsStore} from "../../store/traceAggregatorTabsStore.ts";
import {useTraceAggregatorTreeStore} from "../tree/store/traceAggregatorTreeStore.ts";
import {useTraceAggregatorDataStore} from "../trace/store/traceAggregatorDataStore.ts";
import TraceService from "../services/TraceService.vue";
import TraceId from "../trace/TraceId.vue";
import {useTraceAggregatorProfilingStore} from "../profiling/store/traceAggregatorProfilingStore.ts";

export default defineComponent({
  components: {TraceId, TraceService, FilterTags, TraceAggregatorTraceDataNode, TraceItemMetrics},

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
    dataExpandChange(trace: TraceAggregatorItem) {
      if (this.isTraceDataLoaded(trace.trace.trace_id)) {
        return
      }

      this.traceAggregatorDataStore.findTraceData(trace.trace.trace_id)
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
    isTraceInPayload(traceId: string | null): boolean {
      return !!traceId && traceId === this.payload.trace_id
    }
  },
})
</script>

<style scoped>
</style>
