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
                  :icon="FillTitleIcon"
                  @click="fillTitle"
              />
            </el-tooltip>
          </template>
        </el-input>
        <el-button
            :icon="PlusIcon"
            @click="create()"
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
import {CaretLeft as FillTitleIcon, Plus as PlusIcon, Search as SearchIcon} from '@element-plus/icons-vue'
import {convertDateStringToLocal} from "../../../../../utils/helpers.ts";
import {AdminStore, useTraceAdminStoresStore} from "./store/traceAdminStoresStore.ts";
import {useTraceAggregatorStore} from "../traces/store/traceAggregatorStore.ts";
import TraceAggregatorProfilingNodeData from "../profiling/TraceAggregatorProfilingNodeData.vue";
import {useTraceAggregatorServicesStore} from "../services/store/traceAggregatorServicesStore.ts";

export default defineComponent({
  components: {
    TraceAggregatorProfilingNodeData,
    SearchIcon,
    PlusIcon,
    FillTitleIcon,
  },

  data() {
    return {
      dialogVisible: false,
    }
  },

  computed: {
    traceAdminStoresStore() {
      return useTraceAdminStoresStore()
    },
    traceAggregatorServicesStore() {
      return useTraceAggregatorServicesStore()
    },
    traceAggregatorStore() {
      return useTraceAggregatorStore()
    },
    SearchIcon() {
      return SearchIcon
    },
    PlusIcon() {
      return PlusIcon
    },
    FillTitleIcon() {
      return FillTitleIcon
    },
  },

  methods: {
    update() {
      this.traceAdminStoresStore.findAdminStores()
    },
    create() {
      this.traceAdminStoresStore.create(false)
          .then(() => {
            this.traceAdminStoresStore.clearAdminStoreCreateParameters()

            this.update()
          })
    },
    deleteStore(store: AdminStore) {
      this.traceAdminStoresStore.deleteAdminStore(store.id)
    },
    restore(store: AdminStore) {
      this.traceAdminStoresStore.restoreTraceState(store)

      this.dialogVisible = false

      this.traceAdminStoresStore.clearAdminStoreCreateParameters()
    },
    makeName(store: AdminStore): string {
      return store.title
    },
    makeDate(store: AdminStore): string {
      return convertDateStringToLocal(store.created_at, false)
    },
    fillTitle() {
      this.traceAdminStoresStore.createParameters.title = this.traceAdminStoresStore.generateStoreTitle()
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
