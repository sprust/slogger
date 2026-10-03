<template>
  <p v-for="paragraph in text.paragraphs" class="landing-paragraph">
    <el-text>{{ paragraph }}</el-text>
  </p>

  <p class="landing-caption">
    <el-text type="info">{{ text.examplesCaption }}</el-text>
  </p>
  <el-space wrap :size="8">
    <el-button
        v-for="example in text.examples"
        :key="example.name"
        :type="activeExample === example.name ? 'primary' : 'default'"
        @click="applyExample(example.name)"
    >
      {{ example.label }}
    </el-button>
    <el-button :type="activeExample === '' ? 'primary' : 'default'" @click="applyExample('')">
      {{ text.resetExample }}
    </el-button>
  </el-space>
  <p class="landing-example-text">
    <el-text type="info">{{ activeExampleText }}</el-text>
  </p>

  <el-card shadow="never" body-class="landing-filters-body">
    <el-scrollbar class="landing-filters">
      <el-row align="middle" class="landing-filters-row">
        <el-date-picker
            v-model="period"
            type="datetimerange"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="max-width: 400px"
            :clearable="false"
        />
        <el-text>Services</el-text>
        <el-select v-model="serviceIds" multiple clearable placeholder="Select" style="width: 300px">
          <el-option v-for="service in services" :key="service.id" :label="service.name" :value="service.id"/>
        </el-select>
      </el-row>
      <el-row>
        <FilterTagsBarView :sections="tagSections" @tag-click="onTagSectionClick"/>
      </el-row>
      <el-row v-if="customFields.length" class="landing-custom-fields">
        <TraceAggregatorTracesCustomFields
            :custom-fields="customFields"
            @onCustomFieldClick="onCustomFieldClick"
            @onCustomFieldTypeChange="setCustomFieldType"
        />
      </el-row>
      <el-row>
        <el-button :icon="Plus" link @click="addField">Data field</el-button>
      </el-row>
    </el-scrollbar>
  </el-card>

  <p class="landing-caption">
    <el-text type="info">{{ text.found }} {{ items.length }} {{ text.of }} {{ total }}</el-text>
  </p>
  <el-card shadow="never" body-class="landing-traces-body">
    <el-scrollbar class="landing-traces">
      <TraceTracesTableView
          v-if="items.length"
          :items="items"
          :selected-types="types"
          :selected-tags="tags"
          :selected-statuses="statuses"
          :data-fields="tableFields"
          :data-items="dataItems"
          :search-query="searchQuery"
          :search-in-values="searchInValues"
          :custom-field-names="customFieldNames"
          expanded-width="100%"
          @type-click="(value: string) => toggle(types, value)"
          @tag-click="(value: string) => toggle(tags, value)"
          @status-click="(value: string) => toggle(statuses, value)"
          @custom-field-click="onCustomFieldClick"
          @update:search-query="(value: string) => searchQuery = value"
          @update:search-in-values="(value: boolean) => searchInValues = value"
      />
      <el-empty v-else :description="text.empty"/>
    </el-scrollbar>
  </el-card>
</template>

<script lang="ts">
import {defineComponent, shallowRef} from "vue";
import {Plus} from '@element-plus/icons-vue'
import TraceTracesTableView, {TraceTableDataItem} from "../../trace-aggregator/components/traces/TraceTracesTableView.vue";
import TraceAggregatorTracesCustomFields
  from "../../trace-aggregator/components/traces/TraceAggregatorTracesCustomFields.vue";
import FilterTagsBarView, {FilterTagsBarSection} from "../../trace-aggregator/components/tags/FilterTagsBarView.vue";
import {
  addOrDeleteCustomField,
  makeEmptyCustomField,
  setCustomFieldType
} from "../../trace-aggregator/components/traces/customFields.ts";
import type {
  TraceAggregatorCustomField,
  TraceAggregatorCustomFieldParameter,
  TraceAggregatorCustomFieldType,
  TraceAggregatorItem,
} from "../../trace-aggregator/components/traces/store/traceAggregatorStore.ts";
import type {TraceTagHistoryType} from "../../trace-aggregator/components/tags/store/traceAggregatorTagsStore.ts";
import {LandingTraceListText, landingText} from "../content/ru.ts";
import {DemoTrace, demoTraces, toTraceDetailData} from "../demo/demoTraces.ts";
import {filterDemoTraces} from "../demo/demoTraceFilter.ts";
import {makeFilterExample} from "../demo/demoFilterExamples.ts";
import {demoServiceNames} from "../demo/demoTraceTree.ts";

interface DemoService {
  id: number,
  name: string,
}

const dataItems: Record<string, TraceTableDataItem> = Object.fromEntries(
    demoTraces.map((trace: DemoTrace) => [
      trace.item.trace.trace_id,
      {loaded: true, data: toTraceDetailData(trace.data)},
    ])
)

export default defineComponent({
  components: {TraceTracesTableView, TraceAggregatorTracesCustomFields, FilterTagsBarView},

  data() {
    return {
      Plus: shallowRef(Plus),
      period: ['2026-09-15 14:00:00', '2026-09-15 15:00:00'] as Array<string>,
      serviceIds: [] as Array<number>,
      types: [] as Array<string>,
      tags: [] as Array<string>,
      statuses: [] as Array<string>,
      customFields: makeFilterExample('items') as Array<TraceAggregatorCustomField>,
      activeExample: 'items',
      searchQuery: '',
      searchInValues: false,
    }
  },

  computed: {
    text(): LandingTraceListText {
      return landingText.search.traceList
    },
    services(): Array<DemoService> {
      return Object.entries(demoServiceNames).map(([id, name]) => ({id: Number(id), name}))
    },
    total(): number {
      return demoTraces.length
    },
    items(): Array<TraceAggregatorItem> {
      return filterDemoTraces(demoTraces, {
        serviceIds: this.serviceIds,
        types: this.types,
        tags: this.tags,
        statuses: this.statuses,
        customFields: this.customFields,
      })
    },
    tableFields(): Array<string> {
      return this.customFields
          .filter(customField => customField.addToTable && customField.field.trim() !== '')
          .map(customField => customField.field.trim())
    },
    customFieldNames(): Array<string> {
      return this.customFields.map(customField => customField.field)
    },
    dataItems(): Record<string, TraceTableDataItem> {
      return dataItems
    },
    tagSections(): Array<FilterTagsBarSection> {
      return [
        {key: 'types', label: 'Types', tagType: 'success', selectedTags: this.types},
        {key: 'tags', label: 'Tags', tagType: 'warning', selectedTags: this.tags},
        {key: 'statuses', label: 'Statuses', tagType: 'primary', selectedTags: this.statuses},
      ]
    },
    activeExampleText(): string {
      return this.text.examples.find(example => example.name === this.activeExample)?.text ?? ''
    },
  },

  methods: {
    toggle(values: Array<string>, value: string) {
      const index = values.indexOf(value)

      if (index === -1) {
        values.push(value)
      } else {
        values.splice(index, 1)
      }
    },
    onTagSectionClick(key: TraceTagHistoryType, value: string) {
      this.toggle(this[key], value)
    },
    onCustomFieldClick(parameters: TraceAggregatorCustomFieldParameter) {
      addOrDeleteCustomField(this.customFields, parameters)

      this.activeExample = 'custom'
    },
    setCustomFieldType(customField: TraceAggregatorCustomField, type: TraceAggregatorCustomFieldType) {
      setCustomFieldType(customField, type)
    },
    addField() {
      this.customFields.push(makeEmptyCustomField())

      this.activeExample = 'custom'
    },
    applyExample(name: string) {
      this.customFields = makeFilterExample(name)
      this.activeExample = name
    },
  },
})
</script>

<style scoped>
.landing-paragraph {
  line-height: 1.6;
}

.landing-caption {
  margin: 16px 0 8px 0;
}

.landing-example-text {
  min-height: 22px;
  margin: 8px 0 12px 0;
}

:deep(.landing-filters-body) {
  padding: 12px 16px;
}

.landing-filters {
  height: 230px;
}

.landing-filters-row {
  gap: 10px;
  padding-bottom: 10px;
}

.landing-custom-fields {
  padding-bottom: 10px;
}

:deep(.landing-traces-body) {
  padding: 0;
}

.landing-traces {
  height: 560px;
}
</style>
