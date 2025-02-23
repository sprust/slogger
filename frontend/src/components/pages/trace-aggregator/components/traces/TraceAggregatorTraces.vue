<template>
  <el-scrollbar class="height-100" style="padding-right: 10px">
    <div style="padding-bottom: 10px">
      <el-row>
        <el-space style="padding-right: 5px">
          <div v-if="traceAggregatorGraphStore.showGraph" style="width: 220px">
            <TraceAggregatorTimestampPeriods/>
          </div>
          <div v-else>
            <el-button
                v-if="store.state.startOfDay"
                style="width: 200px"
                @click="store.state.startOfDay = false"
                link
            >
              Start of day
            </el-button>
            <el-date-picker
                v-else
                v-model="store.state.payload.logging_from"
                type="datetime"
                placeholder="From"
                format="YYYY-MM-DD HH:mm:ss"
                date-format="YYYY-MM-DD"
                time-format="HH:mm:ss"
                :shortcuts="dateTimeShortcuts"
                style="width: 200px"
            />
          </div>
        </el-space>
        <el-space style="padding-right: 5px">
          <el-date-picker
              v-model="store.state.payload.logging_to"
              type="datetime"
              placeholder="To"
              format="YYYY-MM-DD HH:mm:ss"
              date-format="YYYY-MM-DD"
              time-format="HH:mm:ss"
              :shortcuts="dateTimeShortcuts"
              style="width: 200px"
          />
        </el-space>
        <el-space>
          <el-text>
            Services
          </el-text>
          <TraceAggregatorServices/>
        </el-space>
        <div class="flex-grow"/>
        <el-form>
          <el-form-item label="Graph">
            <el-space>
              <el-select
                  v-show="traceAggregatorGraphStore.showGraph"
                  v-model="traceAggregatorTimestampFieldsStore.selectedFields"
                  placeholder="Select"
                  style="min-width: 200px"
                  clearable
                  multiple
              >
                <el-option
                    v-for="item in traceAggregatorTimestampFieldsStore.fields"
                    :key="item.value"
                    :label="item.value"
                    :value="item.value"
                />
              </el-select>
              <el-button
                  v-show="traceAggregatorGraphStore.showGraph"
                  size="small"
                  :type="traceAggregatorGraphStore.playGraph ? 'danger' : 'success'"
                  :icon="traceAggregatorGraphStore.playGraph ? Stop : Play"
                  @click="traceAggregatorGraphStore.playGraph = !traceAggregatorGraphStore.playGraph"
              />
              <el-switch
                  v-model="traceAggregatorGraphStore.showGraph"
                  active-text="show"
                  active-color="green"
                  :active-icon="traceAggregatorGraphStore.loading ? Loading : SwitchButton"
              />
            </el-space>
          </el-form-item>
        </el-form>
      </el-row>
      <el-row>
        <FilterTags/>
      </el-row>
      <el-row v-if="store.state.customFields.length" style="padding-bottom: 15px">
        <TraceAggregatorTracesCustomFields
            :custom-fields="store.state.customFields"
            @onCustomFieldClick="onCustomFieldClick"
        />
      </el-row>
      <el-row>
        <OtherFilters/>
        <div class="flex-grow"/>
        <el-space>
          <DynamicIndexes/>
          <AdminStores ref="adminStoresRef"/>
          <el-button @click="reset" :disabled="store.state.loading">
            Reset
          </el-button>
          <el-button @click="onButtonSearchClick" :disabled="store.state.loading || traceAggregatorGraphStore.showGraph">
            Search
          </el-button>
        </el-space>
      </el-row>
    </div>

    <div v-if="traceAggregatorGraphStore.showGraph && traceAggregatorTimestampPeriodStore.loaded">
      <TraceAggregatorGraph/>
    </div>

    <div v-show="!traceAggregatorGraphStore.showGraph" class="height-100">
      <TraceAggregatorTracesPagination
          style="padding-bottom: 5px"
          :disabled="store.state.loading"
          :currentPage="currentPage"
          @on-next-page="onNextPage"
          @on-prev-page="onPrevPage"
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

        <TraceAggregatorTracesPagination
            style="padding-top: 5px"
            :disabled="store.state.loading"
            :currentPage="currentPage"
            @on-next-page="onNextPage"
            @on-prev-page="onPrevPage"
        />
      </div>
    </div>
  </el-scrollbar>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {TracesAddCustomFieldParameter, useTraceAggregatorStore} from "./store/traceAggregatorStore.ts";
import TraceAggregatorTraceDataNode from "../trace/TraceAggregatorTraceDataNode.vue";
import TraceAggregatorTracesTable from "./TraceAggregatorTracesTable.vue";
import TraceAggregatorTracesPagination from "./TraceAggregatorTracesPagination.vue";
import TraceAggregatorTracesCustomFields from "./TraceAggregatorTracesCustomFields.vue";
import TraceAggregatorServices from "../services/TraceAggregatorServices.vue";
import FilterTags from "../tags/FilterTags.vue";
import DynamicIndexes from "../dynamic-indexes/DynamicIndexes.vue";
import AdminStores from "../admin-stores/AdminStores.vue";
import OtherFilters from "./OtherFilters.vue";
import {CaretRight, Close, CloseBold, Loading, SwitchButton} from '@element-plus/icons-vue'
import TraceAggregatorGraph from "../graph/TraceAggregatorGraph.vue";
import TraceAggregatorTimestampPeriods from "../graph/TraceAggregatorTimestampPeriods.vue";
import {useTraceAggregatorGraphStore} from "../graph/store/traceAggregatorGraphStore.ts";
import {useTraceAggregatorTimestampPeriodStore} from "../graph/store/traceAggregatorTimestampPeriodsStore.ts";
import {useTraceAggregatorTimestampFieldsStore} from "../graph/store/traceAggregatorTimestampFieldsStore.ts";
import {useTraceAggregatorDataStore} from "../trace/store/traceAggregatorDataStore.ts";
import {makeStartOfDay} from "../../../../../utils/helpers.ts";


export default defineComponent({
  components: {
    TraceAggregatorGraph,
    TraceAggregatorTracesTable,
    TraceAggregatorTracesPagination,
    FilterTags,
    DynamicIndexes,
    AdminStores,
    OtherFilters,
    TraceAggregatorTraceDataNode,
    TraceAggregatorTracesCustomFields,
    TraceAggregatorServices,
    TraceAggregatorTimestampPeriods,
  },
  data() {
    return {
      store: useTraceAggregatorStore(),
      storeTraceData: useTraceAggregatorDataStore(),
      dateTimeShortcuts: [
        {
          text: 'Start of day',
          value: () => this.setStartOfDay(),
        },
      ]
    }
  },
  computed: {
    traceAggregatorGraphStore() {
      return useTraceAggregatorGraphStore()
    },
    traceAggregatorTimestampFieldsStore() {
      return useTraceAggregatorTimestampFieldsStore()
    },
    traceAggregatorTimestampPeriodStore() {
      return useTraceAggregatorTimestampPeriodStore()
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
    Play() {
      return CaretRight
    },
    Stop() {
      return Close
    },
    Loading() {
      return Loading
    },
    currentPage(): number {
      return this.store.state.payload.page
    }
  },
  methods: {
    setStartOfDay(): Date {
      const startOfDay = makeStartOfDay()

      this.store.state.startOfDay = true

      return startOfDay
    },
    onButtonSearchClick() {
      this.store.dispatch('setPage', 1)
      this.update()

      // @ts-ignore
      this.$refs.adminStoresRef.create(true)
    },
    update() {
      this.store.dispatch('fillTraceAggregator')
      this.storeTraceData.dispatch('clearTraceData')
    },
    onNextPage() {
      ++this.store.state.payload.page

      this.update()
    },
    onPrevPage() {
      if (this.store.state.payload.page === 1) {
        return
      }

      --this.store.state.payload.page

      this.update()
    },
    reset() {
      this.store.dispatch('resetFilters')
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
  watch: {
    'traceAggregatorGraphStore.showGraph'() {
      this.traceAggregatorGraphStore.playGraph = false
    }
  },
  mounted() {
    if (this.store.state.loading) {
      this.reset()

      this.update()
    }

    if (!this.traceAggregatorTimestampFieldsStore.loaded) {
      this.traceAggregatorTimestampFieldsStore.findTimestampFields()
    }
  }
})
</script>

<style scoped>
.flex-grow {
  flex-grow: 1;
}
</style>
