<template>
  <el-scrollbar class="height-100" style="padding-right: 10px">
    <el-row>
      <el-space>
        <el-form>
          <el-form-item label="Logged at">
            <el-space>
              <div v-if="storeGraph.state.showGraph" style="width: 220px">
                <TraceAggregatorTimestampPeriods/>
              </div>
              <el-date-picker
                  v-else
                  v-model="store.state.payload.logging_from"
                  type="datetime"
                  placeholder="From"
                  format="YYYY-MM-DD HH:mm:ss"
                  date-format="YYYY-MM-DD"
                  time-format="HH:mm:ss"
                  :shortcuts="dateTimeShortcuts"
                  style="width: 220px"
              />
              <el-date-picker
                  v-model="store.state.payload.logging_to"
                  type="datetime"
                  placeholder="To"
                  format="YYYY-MM-DD HH:mm:ss"
                  date-format="YYYY-MM-DD"
                  time-format="HH:mm:ss"
                  :shortcuts="dateTimeShortcuts"
              />
            </el-space>
          </el-form-item>
        </el-form>
        <el-form>
          <el-form-item label="Services">
            <TraceAggregatorServices/>
          </el-form-item>
        </el-form>
        <el-form>
          <el-form-item label="Profiling">
            <el-switch
                v-model="store.state.payload.has_profiling"
                size="small"
                active-text="has"
                inactive-text="off"
                active-color="green"
            />
          </el-form-item>
        </el-form>
      </el-space>
      <div class="flex-grow"/>
      <el-form>
        <el-form-item label="Graph">
          <el-space>
            <el-select
                v-model="storeTimestampsFields.state.selectedTimestampFields"
                placeholder="Select"
                style="width: 200px"
                clearable
                multiple
            >
              <el-option
                  v-for="item in storeTimestampsFields.state.timestampFields"
                  :key="item.value"
                  :label="item.value"
                  :value="item.value"
              />
            </el-select>
            <el-switch
                v-model="storeGraph.state.showGraph"
                size="small"
                active-text="show"
                inactive-text="off"
                active-color="green"
                :inactive-icon="Close"
                :active-icon="storeGraph.state.loading ? Loading : SwitchButton"
            />
          </el-space>
        </el-form-item>
      </el-form>
    </el-row>
    <el-row>
      <el-space>
        <el-form :disabled="storeGraph.state.showGraph">
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
          <el-form-item label="Duration">
            <el-space>
              <el-input-number
                  v-model="store.state.payload.duration_from"
                  :precision="6"
                  :step="0.5"
                  :min="0"
                  :max="10"
              />
              <el-input-number
                  v-model="store.state.payload.duration_to"
                  :precision="6"
                  :step="0.5"
                  :min="0"
                  :max="10"
              />
              <el-button :icon="CloseBold" @click="onClearDuration">
              </el-button>
            </el-space>
          </el-form-item>
        </el-form>
      </el-space>
    </el-row>
    <el-row>
      <FilterTags/>
      <div class="flex-grow"/>
      <el-button @click="reset" :disabled="store.state.loading">
        reset
      </el-button>
      <el-button @click="onButtonSearchClick" :disabled="store.state.loading || storeGraph.state.showGraph">
        search
      </el-button>
    </el-row>

    <el-row v-if="store.state.customFields.length">
      <TraceAggregatorTracesCustomFields
          :custom-fields="store.state.customFields"
          @onCustomFieldClick="onCustomFieldClick"
      />
    </el-row>

    <div v-if="storeGraph.state.showGraph && storeTimestampsPeriods.state.loaded">
      <TraceAggregatorGraph/>
    </div>

    <div v-show="!storeGraph.state.showGraph" class="height-100">
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
          :disabled="store.state.loading"
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

      <div v-else>
        <TraceAggregatorTracesTable
            :items="store.state.traceAggregator.items"
            :payload="store.state.payload"
            @onTraceTagClick="onTraceTagClick"
            @onTraceTypeClick="onTraceTypeClick"
            @onTraceStatusClick="onTraceStatusClick"
            @onCustomFieldClick="onCustomFieldClick"
        />

        <el-pagination
            v-if="store.state.traceAggregator.paginator && store.state.traceAggregator.items.length > 0"
            small="small"
            v-model:current-page="store.state.payload.page"
            v-model:page-size="store.state.traceAggregator.paginator.per_page"
            layout="sizes, prev, pager, next, jumper"
            :total="store.state.traceAggregator.paginator.total"
            :page-sizes="[5, 10, 15, 20]"
            @current-change="update"
            @size-change="handlePageSizeChange"
        />
      </div>
    </div>
  </el-scrollbar>
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
import {CloseBold, SwitchButton, Close, Loading} from '@element-plus/icons-vue'
import TraceAggregatorGraph from "./TraceAggregatorGraph.vue";
import TraceAggregatorTimestampPeriods from "./TraceAggregatorTimestampPeriods.vue";
import {useTraceAggregatorGraphStore} from "../../../../store/traceAggregatorGraphStore.ts";
import {useTraceAggregatorTimestampPeriodStore} from "../../../../store/traceAggregatorTimestampPeriodsStore.ts";
import {useTraceAggregatorTimestampFieldsStore} from "../../../../store/traceAggregatorTimestampFieldsStore.ts";

const startOfDay = new Date()
startOfDay.setUTCHours(Math.ceil(startOfDay.getTimezoneOffset() / 60), 0, 0, 0);

export default defineComponent({
  components: {
    TraceAggregatorGraph,
    TraceAggregatorTracesTable,
    FilterTags,
    TraceAggregatorTraceDataNode,
    TraceAggregatorTracesCustomFields,
    TraceAggregatorServices,
    TraceAggregatorTimestampPeriods,
  },
  data() {
    return {
      store: useTraceAggregatorStore(),
      storeGraph: useTraceAggregatorGraphStore(),
      storeTimestampsPeriods: useTraceAggregatorTimestampPeriodStore(),
      storeTimestampsFields: useTraceAggregatorTimestampFieldsStore(),
      dateTimeShortcuts: [
        {
          text: 'Start of day',
          value: startOfDay,
        },
      ]
    }
  },
  computed: {
    state() {
      return state
    },
    CloseBold() {
      return CloseBold
    },
    SwitchButton() {
      return SwitchButton
    },
    Close() {
      return Close
    },
    Loading() {
      return Loading
    },
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

      this.store.state.payload.logging_from = startOfDay.toUTCString()
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
    onClearDuration() {
      this.store.commit('clearDurationFilter')
    }
  },
  mounted() {
    if (this.store.state.loading) {
      this.reset()

      this.update()
    }

    if (!this.storeTimestampsFields.state.loaded) {
      this.storeTimestampsFields.dispatch('findTimestampFields')
    }
  }
})
</script>

<style scoped>
.flex-grow {
  flex-grow: 1;
}
</style>
