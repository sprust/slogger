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
        <FilterTagsBarView
            :sections="tagSections"
            @tag-click="onTagSectionClick"
            @open-dialog="tagsDialogVisible = true"
        />
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

  <el-dialog
      v-model="tagsDialogVisible"
      width="80%"
      top="10px"
      :append-to-body="false"
      style="opacity: .9"
  >
    <template #header>
      <el-text>
        * every column is filtered by period, services and by what is chosen in the columns on its left
      </el-text>
    </template>
    <el-row style="min-height: 60vh">
      <el-col
          v-for="(section, index) in dialogSections"
          :key="section.key"
          :span="8"
      >
        <FilterTagsSection
            :title="section.title"
            :tagType="section.tagType"
            :tags="section.tags"
            :selectedTags="section.selectedTags"
            :recentTags="recentTags[section.key]"
            :loading="notLoading"
            :canMoveLeft="index > 0"
            :canMoveRight="index < dialogSections.length - 1"
            @findTags="(value: string) => searchTexts[section.key] = value"
            @onTagClick="(tag: string) => onTagSectionClick(section.key, tag)"
            @moveLeft="moveSection(section.key, -1)"
            @moveRight="moveSection(section.key, 1)"
        />
      </el-col>
    </el-row>
  </el-dialog>

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
import FilterTagsSection from "../../trace-aggregator/components/tags/FilterTagsSection.vue";
import type {TraceTag} from "../../trace-aggregator/components/tags/store/traceAggregatorTagsStore.ts";
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
import type {LandingTraceListText} from "../content/types.ts";
import {landingText} from "../content/locale.ts";
import {DemoTrace, demoTraces, toTraceDetailData} from "../demo/demoTraces.ts";
import {filterDemoTraces} from "../demo/demoTraceFilter.ts";
import {makeFilterExample} from "../demo/demoFilterExamples.ts";
import {demoServiceNames} from "../demo/demoTraceTree.ts";

interface DemoService {
  id: number,
  name: string,
}

interface DialogSection {
  key: TraceTagHistoryType,
  title: string,
  tagType: string,
  tags: Array<TraceTag>,
  selectedTags: Array<string>,
}

const sectionTitles: Record<TraceTagHistoryType, string> = {
  types: 'Types',
  tags: 'Tags (by first 100000)',
  statuses: 'Statuses',
}

const sectionTagTypes: Record<TraceTagHistoryType, string> = {
  types: 'success',
  tags: 'warning',
  statuses: 'primary',
}

function valuesOf(item: TraceAggregatorItem, key: TraceTagHistoryType): Array<string> {
  if (key === 'types') {
    return [item.trace.type]
  }

  if (key === 'tags') {
    return item.trace.tags
  }

  return [item.trace.status]
}

const dataItems: Record<string, TraceTableDataItem> = Object.fromEntries(
    demoTraces.map((trace: DemoTrace) => [
      trace.item.trace.trace_id,
      {loaded: true, data: toTraceDetailData(trace.data)},
    ])
)

export default defineComponent({
  components: {TraceTracesTableView, TraceAggregatorTracesCustomFields, FilterTagsBarView, FilterTagsSection},

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
      tagsDialogVisible: false,
      sectionOrder: ['types', 'tags', 'statuses'] as Array<TraceTagHistoryType>,
      searchTexts: {types: '', tags: '', statuses: ''} as Record<TraceTagHistoryType, string>,
      recentTags: {types: [], tags: [], statuses: []} as Record<TraceTagHistoryType, Array<string>>,
      notLoading: {loading: false},
    }
  },

  computed: {
    text(): LandingTraceListText {
      return landingText().search.traceList
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
    dialogSections(): Array<DialogSection> {
      return this.sectionOrder.map((key: TraceTagHistoryType, index: number) => {
        const left = this.sectionOrder.slice(0, index)

        const traces = filterDemoTraces(demoTraces, {
          serviceIds: this.serviceIds,
          types: left.includes('types') ? this.types : [],
          tags: left.includes('tags') ? this.tags : [],
          statuses: left.includes('statuses') ? this.statuses : [],
          customFields: this.customFields,
        })

        const counts: Record<string, number> = {}

        traces.forEach((item: TraceAggregatorItem) => {
          valuesOf(item, key).forEach((value: string) => {
            counts[value] = (counts[value] ?? 0) + 1
          })
        })

        const query = this.searchTexts[key].trim().toLowerCase()
        const found: Array<TraceTag> = Object.keys(counts)
            .filter((name: string) => !query || name.toLowerCase().includes(query))
            .map((name: string) => ({name, count: counts[name]}))
            .sort((a: TraceTag, b: TraceTag) => b.count - a.count || a.name.localeCompare(b.name))

        const selected = this[key]
        const missing: Array<TraceTag> = selected
            .filter((name: string) => !found.find((tag: TraceTag) => tag.name === name))
            .map((name: string) => ({name, count: 0}))

        return {
          key,
          title: sectionTitles[key],
          tagType: sectionTagTypes[key],
          tags: [...missing, ...found],
          selectedTags: selected,
        }
      })
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
      const wasSelected = this[key].includes(value)

      this.toggle(this[key], value)

      if (!wasSelected) {
        this.recentTags[key] = [value, ...this.recentTags[key].filter((tag: string) => tag !== value)].slice(0, 10)
      }
    },
    moveSection(key: TraceTagHistoryType, shift: number) {
      const from = this.sectionOrder.indexOf(key)
      const to = from + shift

      if (to < 0 || to >= this.sectionOrder.length) {
        return
      }

      const order = [...this.sectionOrder]

      order.splice(from, 1)
      order.splice(to, 0, key)

      this.sectionOrder = order
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
