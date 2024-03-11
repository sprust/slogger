<template>
  <el-row>
    <el-space>
      <el-form>
        <el-form-item style="width: 500px">
          <el-col :span="11">
            <el-date-picker
                v-model="store.state.payload.logging_from"
                type="datetime"
                placeholder="From"
                format="YYYY-MM-DD HH:mm:ss"
                date-format="YYYY-MM-DD"
                time-format="HH:mm:ss"
                :shortcuts="dateTimeShortcuts"
            />
          </el-col>
          <el-col :span="1" class="text-center">
            <span class="text-gray-500">to</span>
          </el-col>
          <el-col :span="11">
            <el-date-picker
                v-model="store.state.payload.logging_to"
                type="datetime"
                placeholder="To"
                format="YYYY-MM-DD HH:mm:ss"
                date-format="YYYY-MM-DD"
                time-format="HH:mm:ss"
                :shortcuts="dateTimeShortcuts"
            />
          </el-col>
        </el-form-item>
      </el-form>
      <el-form>
        <el-form-item label="Trace id">
          <el-input v-model="store.state.payload.trace_id" style="width: 500px" clearable>
            <template #append>
              <el-checkbox
                  v-model="store.state.payload.all_traces_in_tree"
                  label="All in tree"
                  :disabled="!store.state.payload.trace_id"
              />
            </template>
          </el-input>
        </el-form-item>
      </el-form>
      <el-form>
        <el-form-item label="Services">
          <TraceAggregatorServices/>
        </el-form-item>
      </el-form>
    </el-space>
  </el-row>
  <el-row>
    <FilterTags/>
    <div class="flex-grow"/>
    <el-button @click="reset" :disabled="store.state.loading">reset</el-button>
    <el-button @click="onButtonSearchClick" :disabled="store.state.loading">search</el-button>
  </el-row>

  <el-row v-if="store.state.customFields.length">
    <TraceAggregatorTracesCustomFields
        :custom-fields="store.state.customFields"
        @onCustomFieldClick="onCustomFieldClick"
    />
  </el-row>

  <el-pagination
      v-if="store.state.traceAggregator.paginator"
      small="small"
      v-model:current-page="store.state.payload.page"
      v-model:page-size="store.state.traceAggregator.paginator.per_page"
      layout="sizes, prev, pager, next, jumper"
      :total="store.state.traceAggregator.paginator.total"
      :page-sizes="[5, 10, 15, 20]"
      @current-change="update"
      @size-change="handlePageSizeChange"
  />

  <el-progress
      v-if="store.state.loading"
      status="success"
      :text-inside="true"
      :percentage="100"
      :indeterminate="true"
      :duration="5"
      striped
  />

  <TraceAggregatorTracesTable
      v-else
      :items="store.state.traceAggregator.items"
      :payload="store.state.payload"
      @onTraceTagClick="onTraceTagClick"
      @onTraceTypeClick="onTraceTypeClick"
      @onTraceStatusClick="onTraceStatusClick"
      @onCustomFieldClick="onCustomFieldClick"
  />

  <el-pagination
      v-if="store.state.traceAggregator.paginator && !store.state.loading"
      small="small"
      v-model:current-page="store.state.payload.page"
      v-model:page-size="store.state.traceAggregator.paginator.per_page"
      layout="sizes, prev, pager, next, jumper"
      :total="store.state.traceAggregator.paginator.total"
      :page-sizes="[5, 10, 15, 20]"
      @current-change="update"
      @size-change="handlePageSizeChange"
  />
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {TracesAddCustomFieldParameter, useTraceAggregatorStore} from "../../../../store/traceAggregatorStore.ts";
import TraceAggregatorTraceDataNode from "./TraceAggregatorTraceDataNode.vue";
import TraceAggregatorTracesTable from "./TraceAggregatorTracesTable.vue";
import TraceAggregatorTracesCustomFields from "./TraceAggregatorTracesCustomFields.vue";
import TraceAggregatorServices from "./TraceAggregatorServices.vue";
import FilterTags from "../widgets/FilterTags.vue";
import {state} from "vue-tsc/out/shared";

export default defineComponent({
  computed: {
    state() {
      return state
    }
  },
  components: {
    TraceAggregatorTracesTable,
    FilterTags,
    TraceAggregatorTraceDataNode,
    TraceAggregatorTracesCustomFields,
    TraceAggregatorServices
  },
  data() {
    return {
      store: useTraceAggregatorStore(),
      dateTimeShortcuts: [
        {
          text: 'Now',
          value: new Date(),
        },
        {
          text: 'Yesterday',
          value: () => {
            const date = new Date()
            date.setTime(date.getTime() - 3600 * 1000 * 24)
            return date
          },
        },
        {
          text: 'A week ago',
          value: () => {
            const date = new Date()
            date.setTime(date.getTime() - 3600 * 1000 * 24 * 7)
            return date
          },
        },
      ]
    }
  },
  methods: {
    onButtonSearchClick() {
      this.store.dispatch('setPage', 1)
      this.update()
    },
    update() {
      this.store.dispatch('fillTraceAggregator')
    },
    reset() {
      this.store.dispatch('resetFilters')
    },
    handlePageSizeChange(perPage: number) {
      this.store.dispatch('setPerPage', perPage)

      this.update()
    },
    onTraceTypeClick(type: string) {
      this.store.dispatch('addOrDeleteType', type)
    },
    onTraceTagClick(tag: string) {
      this.store.dispatch('addOrDeleteTag', tag)
    },
    onTraceStatusClick(status: string) {
      this.store.dispatch('addOrDeleteStatus', status)
    },
    onCustomFieldClick(parameters: TracesAddCustomFieldParameter) {
      this.store.commit('addOrDeleteCustomField', parameters)
    },
  },
  mounted() {
    if (!this.store.state.loading) {
      return
    }

    this.store.dispatch('resetFilters')

    this.update()
  }
})
</script>

<style scoped>
.flex-grow {
  flex-grow: 1;
}
</style>
