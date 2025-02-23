<template>
  <el-button @click="dialogVisible = true">
    Presets
  </el-button>

  <el-dialog
      v-model="dialogVisible"
      width="80%"
      top="10px"
      :append-to-body="true"
  >
    <el-row>
      <el-space>
        <el-text size="default">
          Filter
        </el-text>
        <el-input
            v-model="traceAdminStoresStore.findParameters.search_query"
            placeholder="Search query"
            style="width: 300px"
            clearable
        >
          <template #append>
            <el-tooltip content="Requests" placement="top-start">
              <el-checkbox v-model="traceAdminStoresStore.findParameters.auto"/>
            </el-tooltip>
          </template>
        </el-input>
        <el-button
            :icon="SearchIcon"
            @click="update"
            :loading="traceAdminStoresStore.loading"
        />
        <el-text size="default">
          Create
        </el-text>
        <el-input
            v-model="traceAdminStoresStore.createParameters.title"
            placeholder="Title"
            style="width: 500px"
            clearable
        >
          <template #append>
            <el-tooltip content="Fill title" placement="top-start">
              <el-button
                  :icon="FillTitleIvon"
                  @click="fillTitle"
              />
            </el-tooltip>
          </template>
        </el-input>
        <el-button
            :icon="PlusIcon"
            @click="create(false)"
            :disabled="!traceAdminStoresStore.createParameters.title"
        />
      </el-space>
    </el-row>
    <el-table
        :data="traceAdminStoresStore.adminStores.items"
        style="height: 80vh; width: 100%"
    >
      <el-table-column type="expand">
        <template #default="props">
          <pre>{{ props.row.store_data }}</pre>
        </template>
      </el-table-column>
      <el-table-column label="Title">
        <template #default="props">
          <el-row>
            {{ makeName(props.row) }}
          </el-row>
        </template>
      </el-table-column>
      <el-table-column label="Created at" fixed="right" min-width="25">
        <template #default="props">
          {{ makeDate(props.row) }}
        </template>
      </el-table-column>
      <el-table-column fixed="right" min-width="20">
        <template #default="props">
          <el-space>
            <el-button
                type="success"
                link
                @click="restore(props.row)"
            >
              restore
            </el-button>
            <el-button
                type="danger"
                link
                :disabled="traceAdminStoresStore.deletedIds[props.row.id]"
                @click="deleteStore(props.row)"
            >
              {{ traceAdminStoresStore.deletedIds[props.row.id] ? 'deleted' : 'delete' }}
            </el-button>
          </el-space>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination
        v-model:current-page="traceAdminStoresStore.findParameters.page"
        background
        layout="prev, pager, next"
        :page-size="traceAdminStoresStore.adminStores.paginator.per_page"
        :total="traceAdminStoresStore.adminStores.paginator.total"
        class="mt-4"
        @current-change="update"
    />
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {CaretLeft as FillTitleIvon, Plus as PlusIcon, Search as SearchIcon} from '@element-plus/icons-vue'
import {
  convertDateStringToLocal,
  makeGeneralFiltersTitles,
  makeOtherFiltersTitles
} from "../../../../../utils/helpers.ts";
import {AdminStore, useTraceAdminStoresStore} from "./store/traceAdminStoresStore.ts";
import {TraceStateParameters, useTraceAggregatorStore} from "../traces/store/traceAggregatorStore.ts";
import TraceAggregatorProfilingNodeData from "../profiling/TraceAggregatorProfilingNodeData.vue";
import {useTraceAggregatorServicesStore} from "../services/store/traceAggregatorServicesStore.ts";

export default defineComponent({
  components: {
    TraceAggregatorProfilingNodeData,
    SearchIcon,
    PlusIcon,
    FillTitleIvon,
  },

  data() {
    return {
      dialogVisible: false,
      traceStore: useTraceAggregatorStore(),
      servicesStore: useTraceAggregatorServicesStore(),
    }
  },

  methods: {
    update() {
      this.traceAdminStoresStore.findParameters.version = this.traceStore.state.version

      this.traceAdminStoresStore.findAdminStores()
    },
    create(auto: boolean) {
      if (auto) {
        this.traceAdminStoresStore.createParameters.title = this.generateTitle()
      }

      if (!this.traceAdminStoresStore.createParameters.title) {
        return
      }

      this.traceAdminStoresStore.createParameters.store_version = this.traceStore.state.version
      this.traceAdminStoresStore.createParameters.store_data = this.serializeTraceState()
      this.traceAdminStoresStore.findParameters.auto = auto
      this.traceAdminStoresStore.createParameters.auto = auto

      this.traceAdminStoresStore.createAdminStore()
          .then(() => {
            this.traceAdminStoresStore.clearAdminStoreCreateParameters()

            this.update()
          })
    },
    deleteStore(store: AdminStore) {
      this.traceAdminStoresStore.deleteAdminStore(store.id)
    },
    restore(store: AdminStore) {
      this.traceStore.dispatch('restoreTraceState', this.unSerializeTraceState(store))

      this.dialogVisible = false

      this.traceAdminStoresStore.clearAdminStoreCreateParameters()
    },
    makeName(store: AdminStore): string {
      return store.title
    },
    makeDate(store: AdminStore): string {
      return convertDateStringToLocal(store.created_at, false)
    },
    serializeTraceState() {
      const state: TraceStateParameters = {
        startOfDay: this.traceStore.state.startOfDay,
        payload: this.traceStore.state.payload,
        customFields: this.traceStore.state.customFields,
      }

      return JSON.stringify(state)
    },
    unSerializeTraceState(store: AdminStore): TraceStateParameters {
      return JSON.parse(store.store_data)
    },
    fillTitle() {
      this.traceAdminStoresStore.createParameters.title = this.generateTitle()
    },
    generateTitle(): string {
      const titles: string[] = [
        ...makeGeneralFiltersTitles(this.traceStore.state, this.servicesStore.state.items),
        ...makeOtherFiltersTitles(this.traceStore.state.payload),
      ]

      return titles.join(' | ')
    }
  },

  computed: {
    traceAdminStoresStore() {
      return useTraceAdminStoresStore()
    },
    SearchIcon() {
      return SearchIcon
    },
    PlusIcon() {
      return PlusIcon
    },
    FillTitleIvon() {
      return FillTitleIvon
    },
  },
  mounted() {
    if (this.traceAdminStoresStore.loading) {
      this.update()
    }
  }
})
</script>

<style scoped>

</style>
