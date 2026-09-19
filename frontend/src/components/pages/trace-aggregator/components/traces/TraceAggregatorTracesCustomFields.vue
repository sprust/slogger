<template>
  <el-table :data="customFields" style="width: 100%">
    <el-table-column min-width="160">
      <template #default="scope">
        <el-input
            v-model="scope.row.field"
            :placeholder="scope.row.manual ? 'request.user.id' : ''"
        >
          <template v-if="scope.row.manual" #append>
            <el-select
                :model-value="typeOf(scope.row.searchData)"
                style="width: 80px"
                @update:model-value="onTypeChange(scope.row, $event)"
            >
              <el-option label="string" value="string"/>
              <el-option label="int" value="int"/>
              <el-option label="float" value="float"/>
              <el-option label="bool" value="bool"/>
            </el-select>
          </template>
        </el-input>
      </template>
    </el-table-column>
    <el-table-column width="170">
      <template #default="scope">
        <el-tooltip
            :content="filterDisabledReason(scope.row)"
            :disabled="filterDisabledReason(scope.row) === ''"
            placement="top"
        >
          <el-select
              :model-value="filterModeOf(scope.row)"
              :disabled="filterDisabledReason(scope.row) !== ''"
              style="width: 150px"
              @update:model-value="setFilterMode(scope.row, $event)"
          >
            <el-option label="No filter" value="none"/>
            <el-option label="By value" value="value"/>
            <el-option label="Null" value="null"/>
            <el-option label="Not null" value="not_null"/>
            <el-option label="Exists" value="exists"/>
            <el-option label="Not exists" value="not_exists"/>
          </el-select>
        </el-tooltip>
      </template>
    </el-table-column>
    <el-table-column width="120">
      <template #default="scope">
        <el-select
            v-if="isValueInt(scope.row.searchData) || isValueFloat(scope.row.searchData)"
            v-model="scope.row.searchData.number.comp"
            :disabled="!isValueFilterActive(scope.row)"
            style="width: 100px"
        >
          <el-option label="=" value="="/>
          <el-option label="!=" value="!="/>
          <el-option label=">" value=">"/>
          <el-option label=">=" value=">="/>
          <el-option label="<" value="<"/>
          <el-option label="<=" value="<="/>
        </el-select>
        <el-select
            v-else-if="!isValueBool(scope.row.searchData)"
            v-model="scope.row.searchData.string.comp"
            :disabled="!isValueFilterActive(scope.row)"
            style="width: 100px"
        >
          <el-option label="equals" value="equals"/>
          <el-option label="not equals" value="not_equals"/>
          <el-option label="contains" value="contains"/>
          <el-option label="starts" value="starts"/>
          <el-option label="ends" value="ends"/>
        </el-select>
      </template>
    </el-table-column>
    <el-table-column>
      <template #default="scope">
        <el-input-number
            v-if="isValueInt(scope.row.searchData)"
            v-model="scope.row.searchData.number.value"
            :disabled="!isValueFilterActive(scope.row)"
            class="search-input"
        />
        <el-input-number
            v-else-if="isValueFloat(scope.row.searchData)"
            v-model="scope.row.searchData.number.value"
            :precision="10"
            :step="0.01"
            :disabled="!isValueFilterActive(scope.row)"
            class="search-input"
        />
        <el-checkbox
            v-else-if="isValueBool(scope.row.searchData)"
            v-model="scope.row.searchData.boolean.value"
            :disabled="!isValueFilterActive(scope.row)"
            class="search-input"
        />
        <el-input
            v-else
            v-model="scope.row.searchData.string.value"
            :disabled="!isValueFilterActive(scope.row)"
            class="search-input"
            clearable
        />
      </template>
    </el-table-column>
    <el-table-column>
      <template #default="scope">
        <el-checkbox
            v-model="scope.row.addToTable"
            :disabled="isKeyEmpty(scope.row)"
            label="Add to table"
            class="content-center"
        />
      </template>
    </el-table-column>
    <el-table-column>
      <template #default="scope">
        <el-checkbox
            v-model="scope.row.addToGraph"
            label="Add to graph"
            class="content-center"
            :disabled="isKeyEmpty(scope.row)
              || (!isValueInt(scope.row.searchData) && !isValueFloat(scope.row.searchData))"
        />
      </template>
    </el-table-column>
    <el-table-column align="right">
      <template #default="scope">
        <el-button
            type="danger"
            :icon="Delete"
            @click="onCustomFieldClick(scope.row.field, scope.row.value, scope.row.canBeFiltered)"
            circle/>
      </template>
    </el-table-column>
  </el-table>
</template>

<script lang="ts">
import {defineComponent, PropType, shallowRef} from 'vue'
import {
  TraceAggregatorCustomField,
  TraceAggregatorCustomFieldParameter,
  TraceAggregatorCustomFieldSearchParameter,
  TraceAggregatorCustomFieldType,
  useTraceAggregatorStore
} from "./store/traceAggregatorStore.ts";
import {Delete} from '@element-plus/icons-vue'

type FilterMode = 'none' | 'value' | 'null' | 'not_null' | 'exists' | 'not_exists'
import {TypesHelper} from '../../../../../utils/helpers.ts'

export default defineComponent({
  emits: ["onCustomFieldClick"],

  props: {
    customFields: {
      type: Array as PropType<TraceAggregatorCustomField[]>,
      required: true,
    },
  },

  data() {
    return {
      directions: ['asc', 'desc'],
      Delete: shallowRef(Delete),
      TypesHelper,
    }
  },

  computed: {
    traceAggregatorStore() {
      return useTraceAggregatorStore()
    },
  },

  methods: {
    typeOf(searchData: TraceAggregatorCustomFieldSearchParameter): TraceAggregatorCustomFieldType {
      if (this.isValueBool(searchData)) {
        return 'bool'
      }

      if (this.isValueFloat(searchData)) {
        return 'float'
      }

      if (this.isValueInt(searchData)) {
        return 'int'
      }

      return 'string'
    },
    isKeyEmpty(customField: TraceAggregatorCustomField): boolean {
      return customField.field.trim() === ''
    },
    filterDisabledReason(customField: TraceAggregatorCustomField): string {
      if (this.isKeyEmpty(customField)) {
        return 'Enter a key first'
      }

      if (!customField.canBeFiltered) {
        return "Values inside a list can't be filtered"
      }

      return ''
    },
    filterModeOf(customField: TraceAggregatorCustomField): FilterMode {
      if (!customField.search) {
        return 'none'
      }

      if (customField.searchData.exists?.enabled) {
        return customField.searchData.exists.value ? 'exists' : 'not_exists'
      }

      if (customField.searchData.null.enabled) {
        return customField.searchData.null.value ? 'null' : 'not_null'
      }

      return 'value'
    },
    isValueFilterActive(customField: TraceAggregatorCustomField): boolean {
      return this.filterDisabledReason(customField) === '' && this.filterModeOf(customField) === 'value'
    },
    setFilterMode(customField: TraceAggregatorCustomField, mode: FilterMode) {
      customField.search = mode !== 'none'
      customField.searchData.null.enabled = mode === 'null' || mode === 'not_null'
      customField.searchData.null.value = mode === 'null'
      customField.searchData.exists = {
        enabled: mode === 'exists' || mode === 'not_exists',
        value: mode === 'exists',
      }
    },
    onTypeChange(customField: TraceAggregatorCustomField, type: TraceAggregatorCustomFieldType) {
      this.traceAggregatorStore.setCustomFieldType(customField, type)
    },
    isValueInt(searchData: TraceAggregatorCustomFieldSearchParameter): boolean {
      return !!(searchData.number
          && !searchData.number.float
          && TypesHelper.isValueInt(searchData.number.value))
    },
    isValueFloat(searchData: TraceAggregatorCustomFieldSearchParameter): boolean {
      return !!(searchData.number
          && (searchData.number.float || TypesHelper.isValueFloat(searchData.number.value)))
    },
    isValueBool(searchData: TraceAggregatorCustomFieldSearchParameter): boolean {
      return !!searchData.boolean
    },
    onCustomFieldClick(customField: string, value: any, canBeFiltered: boolean) {
      const parameters: TraceAggregatorCustomFieldParameter = {
        field: customField,
        value: value,
        canBeFiltered: canBeFiltered,
      }

      this.$emit('onCustomFieldClick', parameters)
    }
  }
})
</script>

<style scoped>
.search-input {
  width: 100%;
}

.content-center {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
