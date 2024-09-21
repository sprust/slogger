<template>
  <el-button @click="dialogVisible = true">
    Presets
  </el-button>

  <el-dialog
      v-model="dialogVisible"
      width="50%"
      top="10px"
      :append-to-body="true"
  >
    <el-row>
      <el-space>
        <el-text size="default">
          Filter
        </el-text>
        <el-input
            v-model="store.state.adminStoresParameters.search_query"
            placeholder="Search query"
            style="width: 300px"
            clearable
        />
        <el-button
            :icon="SearchIcon"
            @click="update"
            :loading="store.state.loading"
        />
        <el-text size="default">
          Create
        </el-text>
        <el-input
            v-model="store.state.adminStoreCreateParameters.title"
            placeholder="Title (min 10)"
            style="width: 300px"
            clearable
        />
        <el-button
            :icon="PlusIcon"
            @click="create"
            :disabled="!store.state.adminStoreCreateParameters.title
              || store.state.adminStoreCreateParameters.title.length < 10"
        />
      </el-space>
    </el-row>
    <el-table
        :data="store.state.adminStores.items"
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
                :disabled="store.state.adminStoreDeletedIds[props.row.id]"
                @click="deleteStore(props.row)"
            >
              {{ store.state.adminStoreDeletedIds[props.row.id] ? 'deleted' : 'delete' }}
            </el-button>
          </el-space>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination
        v-model:current-page="store.state.adminStoresParameters.page"
        background
        layout="prev, pager, next"
        :page-size="store.state.adminStores.paginator.per_page"
        :total="store.state.adminStores.paginator.total"
        class="mt-4"
        @current-change="update"
    />
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {Plus as PlusIcon, Search as SearchIcon} from '@element-plus/icons-vue'
import {convertDateStringToLocal} from "../../../../utils/helpers.ts";
import {AdminStore, useTraceAdminStoresStoreStore} from "../../../../store/traceAdminStoresStore.ts";
import {state} from "vue-tsc/out/shared";
import {TraceStateParameters, useTraceAggregatorStore} from "../../../../store/traceAggregatorStore.ts";

export default defineComponent({
  components: {
    SearchIcon,
    PlusIcon,
  },
  data() {
    return {
      dialogVisible: false,
      store: useTraceAdminStoresStoreStore(),
      traceStore: useTraceAggregatorStore(),
    }
  },

  methods: {
    update() {
      this.store.state.adminStoresParameters.version = this.traceStore.state.version

      this.store.dispatch('findAdminStores')
    },
    create() {
      if (!this.store.state.adminStoreCreateParameters.title) {
        return
      }

      this.store.state.adminStoreCreateParameters.store_version = this.traceStore.state.version
      this.store.state.adminStoreCreateParameters.store_data = this.serializeTraceState()

      this.store.dispatch('createAdminStore')
          .then(() => {
            this.store.dispatch('clearAdminStoreCreateParameters')

            this.update()
          })
    },
    deleteStore(store: AdminStore) {
      if (!confirm('Do you want delete store?')) {
        return
      }

      this.store.dispatch('deleteAdminStore', store.id)
    },
    restore(store: AdminStore) {
      this.traceStore.dispatch('restoreTraceState', this.unSerializeTraceState(store))

      this.dialogVisible = false

      this.store.dispatch('clearAdminStoreCreateParameters')
    },
    makeName(store: AdminStore): string {
      return store.title
    },
    makeDate(store: AdminStore): string {
      return convertDateStringToLocal(store.created_at, false)
    },
    serializeTraceState() {
      const state: TraceStateParameters = {
        payload: this.traceStore.state.payload,
        customFields: this.traceStore.state.customFields,
      }

      return JSON.stringify(state)
    },
    unSerializeTraceState(store: AdminStore): TraceStateParameters {
      return JSON.parse(store.store_data)
    }
  },

  computed: {
    state() {
      return state
    },
    SearchIcon() {
      return SearchIcon
    },
    PlusIcon() {
      return PlusIcon
    },
  },
  mounted() {
    if (this.store.state.loading) {
      this.update()
    }
  }
})
</script>

<style scoped>

</style>
