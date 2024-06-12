<template>
  <el-table :data="customFields" style="width: 100%">
    <el-table-column>
      <template #default="scope">
        <el-input
            v-model="scope.row.field"
        />
      </template>
    </el-table-column>
    <el-table-column>
      <template #default="scope">
        <el-checkbox
            v-model="scope.row.search"
            label="Search"
            class="content-right"
        />
      </template>
    </el-table-column>
    <el-table-column>
      <template #default="scope">
        <el-checkbox
            v-model="scope.row.searchData.null.enabled"
            label="Null"
            :disabled="!scope.row.search"
            class="content-right"
        />
      </template>
    </el-table-column>
    <el-table-column>
      <template #default="scope">
        <el-checkbox
            v-model="scope.row.searchData.null.value"
            :disabled="!scope.row.search || !scope.row.searchData.null.enabled"
            class="content-left"
        />
      </template>
    </el-table-column>
    <el-table-column>
      <template #default="scope">
        <el-input-number
            v-if="isValueInt(scope.row.searchData)"
            v-model="scope.row.searchData.number.value"
            :disabled="!scope.row.search || scope.row.searchData.null.enabled"
            class="search-input"
        />
        <el-input-number
            v-else-if="isValueFloat(scope.row.searchData)"
            v-model="scope.row.searchData.number.value"
            :precision="10"
            :step="0.01"
            :disabled="!scope.row.search || scope.row.searchData.null.enabled"
            class="search-input"
        />
        <el-checkbox
            v-else-if="isValueBool(scope.row.searchData)"
            v-model="scope.row.searchData.boolean.value"
            :disabled="!scope.row.search || scope.row.searchData.null.enabled"
            class="search-input"
        />
        <el-input
            v-else
            v-model="scope.row.searchData.string.value"
            :disabled="!scope.row.search || scope.row.searchData.null.enabled"
            class="search-input"
            clearable
        />
      </template>
    </el-table-column>
    <el-table-column>
      <template #default="scope">
        <el-select
            v-if="isValueInt(scope.row.searchData)"
            v-model="scope.row.searchData.number.comp"
            :disabled="!scope.row.search || scope.row.searchData.null.enabled"
            style="width: 70px"
            class="content-comp"
        >
          <el-option label="=" value="="/>
          <el-option label="!=" value="!="/>
          <el-option label=">" value=">"/>
          <el-option label=">=" value=">="/>
          <el-option label="<" value="<"/>
          <el-option label="<=" value="<="/>
        </el-select>
        <el-select
            v-else-if="isValueFloat(scope.row.searchData)"
            v-model="scope.row.searchData.number.comp"
            :disabled="!scope.row.search || scope.row.searchData.null.enabled"
            style="width: 70px"
            class="content-comp"
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
            :disabled="!scope.row.search || scope.row.searchData.null.enabled"
            style="width: 100px"
            class="content-comp"
        >
          <el-option label="equals" value="equals"/>
          <el-option label="contains" value="contains"/>
          <el-option label="starts" value="starts"/>
          <el-option label="ends" value="ends"/>
        </el-select>
      </template>
    </el-table-column>
    <el-table-column>
      <template #default="scope">
        <el-checkbox
            v-model="scope.row.sort"
            label="Sort"
            class="content-right"
        />
      </template>
    </el-table-column>
    <el-table-column>
      <template #default="scope">
        <el-select
            v-model="scope.row.sortDirection"
            style="width: 80px"
            :disabled="!scope.row.sort"
        >
          <el-option
              v-for="direction in directions"
              :key="direction"
              :label="direction"
              :value="direction"
          />
        </el-select>
      </template>
    </el-table-column>
    <el-table-column>
      <template #default="scope">
        <el-checkbox
            v-model="scope.row.addToTable"
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
            :disabled="!isValueInt(scope.row.searchData)
              && !isValueFloat(scope.row.searchData)"
        />
      </template>
    </el-table-column>
    <el-table-column align="right">
      <template #default="scope">
        <el-button
            type="danger"
            :icon="Delete"
            @click="onCustomFieldClick(scope.row.field, scope.row.value)"
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
  TraceAggregatorCustomFieldSearchParameter
} from "../../../../store/traceAggregatorStore.ts";
import {Delete} from '@element-plus/icons-vue'
import {TypesHelper} from '../../../../utils/helpers.ts'

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
  methods: {
    isValueInt(searchData: TraceAggregatorCustomFieldSearchParameter): boolean {
      return !!(searchData.number && TypesHelper.isValueInt(searchData.number.value))
    },
    isValueFloat(searchData: TraceAggregatorCustomFieldSearchParameter): boolean {
      return !!(searchData.number && TypesHelper.isValueFloat(searchData.number.value))
    },
    isValueBool(searchData: TraceAggregatorCustomFieldSearchParameter): boolean {
      return !!searchData.boolean
    },
    onCustomFieldClick(customField: string, value: any) {
      const parameters: TraceAggregatorCustomFieldParameter = {
        field: customField,
        value: value
      }

      this.$emit('onCustomFieldClick', parameters)
    }
  }
})
</script>

<style scoped>
.content-right {
  display: flex;
  justify-content: right;
  align-items: center;
}

.content-left {
  display: flex;
  justify-content: left;
  align-items: center;
}

.search-input {
  width: 100%;
}

.content-comp {
  justify-content: left;
  align-items: center;
}

.content-center {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
