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
                v-if="traceAggregatorStore.startOfDay"
                style="width: 200px"
                @click="traceAggregatorStore.startOfDay = false"
                link
            >
              Start of day
            </el-button>
            <el-date-picker
                v-else
                v-model="traceAggregatorStore.payload.logging_from"
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
              v-model="traceAggregatorStore.payload.logging_to"
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
      <el-row v-if="traceAggregatorStore.customFields.length" style="padding-bottom: 15px">
        <TraceAggregatorTracesCustomFields
            :custom-fields="traceAggregatorStore.customFields"
            @onCustomFieldClick="onCustomFieldClick"
        />
      </el-row>
      <el-row>
        <OtherFilters/>
        <div class="flex-grow"/>
        <el-space>
          <DynamicIndexes/>
          <AdminStores ref="adminStoresRef"/>
          <el-button @click="reset" :disabled="traceAggregatorStore.loading">
            Reset
          </el-button>
          <el-button @click="onButtonSearchClick"
                     :disabled="traceAggregatorStore.loading || traceAggregatorGraphStore.showGraph">
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
          :disabled="traceAggregatorStore.loading"
          :currentPage="currentPage"
          @on-next-page="onNextPage"
          @on-prev-page="onPrevPage"
      />

      <el-progress
          v-if="traceAggregatorStore.loading"
          status="success"
          :text-inside="true"
          :percentage="100"
          :indeterminate="true"
          :duration="5"
          striped
      />

      <div v-else>
        <TraceAggregatorTracesTable
            :items="traceAggregatorStore.traceAggregator.items"
            :payload="traceAggregatorStore.payload"
            @onTraceTagClick="onTraceTagClick"
            @onTraceTypeClick="onTraceTypeClick"
            @onTraceStatusClick="onTraceStatusClick"
            @onCustomFieldClick="onCustomFieldClick"
        />

        <TraceAggregatorTracesPagination
            style="padding-top: 5px"
            :disabled="traceAggregatorStore.loading"
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
import {TraceAggregatorCustomFieldParameter, useTraceAggregatorStore} from "./store/traceAggregatorStore.ts";
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
import {useTraceAdminStoresStore} from "../admin-stores/store/traceAdminStoresStore.ts";

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
      dateTimeShortcuts: [
        {
          text: 'Start of day',
          value: () => this.setStartOfDay(),
        },
      ]
    }
  },

  computed: {
    traceAggregatorStore() {
      return useTraceAggregatorStore()
    },
    traceAdminStoresStore() {
      return useTraceAdminStoresStore()
    },
    traceAggregatorGraphStore() {
      return useTraceAggregatorGraphStore()
    },
    traceAggregatorTimestampFieldsStore() {
      return useTraceAggregatorTimestampFieldsStore()
    },
    traceAggregatorTimestampPeriodStore() {
      return useTraceAggregatorTimestampPeriodStore()
    },
    traceAggregatorDataStore() {
      return useTraceAggregatorDataStore()
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
      return this.traceAggregatorStore.payload.page
    }
  },
  methods: {
    setStartOfDay(): Date {
      const startOfDay = makeStartOfDay()

      this.traceAggregatorStore.startOfDay = true

      return startOfDay
    },
    onButtonSearchClick() {
      this.traceAggregatorStore.setPage(1)
      this.update()

      this.traceAdminStoresStore.create(true)
    },
    update() {
      this.traceAggregatorStore.fillTraceAggregator()
      this.traceAggregatorDataStore.$reset()
    },
    onNextPage() {
      ++this.traceAggregatorStore.payload.page

      this.update()
    },
    onPrevPage() {
      if (this.traceAggregatorStore.payload.page === 1) {
        return
      }

      --this.traceAggregatorStore.payload.page

      this.update()
    },
    reset() {
      this.traceAggregatorStore.resetFilters()
    },
    onTraceTypeClick(type: string) {
      this.traceAggregatorStore.addOrDeleteType(type)
    },
    onTraceTagClick(tag: string) {
      this.traceAggregatorStore.addOrDeleteTag(tag)
    },
    onTraceStatusClick(status: string) {
      this.traceAggregatorStore.addOrDeleteStatus(status)
    },
    onCustomFieldClick(parameters: TraceAggregatorCustomFieldParameter) {
      this.traceAggregatorStore.addOrDeleteCustomField(parameters)
    },
  },
  watch: {
    'traceAggregatorGraphStore.showGraph'() {
      this.traceAggregatorGraphStore.playGraph = false
    }
  },
  mounted() {
    if (this.traceAggregatorStore.loading) {
      this.reset()

      this.update()
    }

    if (!this.traceAggregatorTimestampFieldsStore.loaded) {
      this.traceAggregatorTimestampFieldsStore.findTimestampFields()
    }


    if (!this.traceAggregatorTimestampPeriodStore.loaded) {
      this.traceAggregatorTimestampPeriodStore.findTimestampPeriods()
    }
  }
})
</script>

<style scoped>
.flex-grow {
  flex-grow: 1;
}
</style>
