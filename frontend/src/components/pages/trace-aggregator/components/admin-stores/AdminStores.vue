<template>
  <el-button @click="open">
    Presets
  </el-button>

  <el-dialog
      v-model="dialogVisible"
      width="80%"
      top="10px"
      style="margin-bottom: 0"
      :append-to-body="true"
  >
    <template #header>
      <el-text size="large">Presets</el-text>
    </template>

    <el-tabs :model-value="tab" @update:model-value="onTabChange">
      <el-tab-pane label="Saved" name="saved"/>
      <el-tab-pane label="History" name="history"/>
    </el-tabs>

    <el-row class="toolbar-row">
      <el-input
          v-model="traceAdminStoresStore.createParameters.title"
          :disabled="tab !== 'saved'"
          placeholder="Preset name"
          class="preset-name-input"
          style="width: 500px"
          clearable
          @keyup.enter="create"
      >
        <template #append>
          <div class="append-buttons">
            <el-tooltip content="Name from filters" placement="top" :show-after="1000">
              <el-button :icon="FillTitleIcon" :disabled="tab !== 'saved'" @click="fillTitle"/>
            </el-tooltip>
            <el-tooltip content="Save current filters" placement="top" :show-after="1000">
              <el-button :icon="PlusIcon" :disabled="!canCreate" @click="create"/>
            </el-tooltip>
          </div>
        </template>
      </el-input>
    </el-row>

    <el-row class="toolbar-row">
      <el-space>
        <el-input
            v-model="traceAdminStoresStore.findParameters.search_query"
            placeholder="Search by title"
            style="width: 300px"
            clearable
            @keyup.enter="search"
            @clear="search"
        />
        <el-button
            :icon="SearchIcon"
            :loading="traceAdminStoresStore.loading"
            @click="search"
        />
      </el-space>
    </el-row>

    <el-table
        :data="traceAdminStoresStore.adminStores.items"
        :empty-text="emptyText"
        style="height: calc(100vh - 300px); width: 100%"
        v-loading="traceAdminStoresStore.loading"
    >
      <el-table-column type="expand">
        <template #default="props">
          <pre>{{ prettyData(props.row) }}</pre>
        </template>
      </el-table-column>
      <el-table-column label="Title" prop="title"/>
      <el-table-column label="Created at" prop="created_at" width="200"/>
      <el-table-column width="160" align="right">
        <template #default="props">
          <el-text v-if="traceAdminStoresStore.deletedIds[props.row.id]" type="info">
            Deleted
          </el-text>
          <el-space v-else>
            <el-button type="primary" link @click="restore(props.row)">
              Apply
            </el-button>
            <el-popconfirm
                title="Delete this preset?"
                confirm-button-text="Delete"
                cancel-button-text="Cancel"
                @confirm="deleteStore(props.row)"
            >
              <template #reference>
                <el-button type="danger" link>
                  Delete
                </el-button>
              </template>
            </el-popconfirm>
          </el-space>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
        :class="{'is-hidden': traceAdminStoresStore.adminStores.paginator.total_pages <= 1}"
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
import {AdminStore, useTraceAdminStoresStore} from "./store/traceAdminStoresStore.ts";

type PresetsTab = 'saved' | 'history'

export default defineComponent({
  data() {
    return {
      dialogVisible: false,
    }
  },

  computed: {
    traceAdminStoresStore() {
      return useTraceAdminStoresStore()
    },
    SearchIcon() {
      return SearchIcon
    },
    FillTitleIcon() {
      return FillTitleIcon
    },
    PlusIcon() {
      return PlusIcon
    },
    tab(): PresetsTab {
      return this.traceAdminStoresStore.findParameters.auto ? 'history' : 'saved'
    },
    canCreate(): boolean {
      return this.tab === 'saved' && this.traceAdminStoresStore.createParameters.title.trim() !== ''
    },
    emptyText(): string {
      return this.tab === 'saved'
          ? 'No saved presets yet. Set up the filters and save them above.'
          : 'No searches yet'
    },
  },

  methods: {
    open() {
      this.dialogVisible = true

      this.update()
    },
    update() {
      this.traceAdminStoresStore.findAdminStores()
    },
    search() {
      this.traceAdminStoresStore.findParameters.page = 1

      this.update()
    },
    onTabChange(tab: string | number) {
      this.traceAdminStoresStore.findParameters.auto = tab === 'history'

      this.search()
    },
    create() {
      if (!this.canCreate) {
        return
      }

      this.traceAdminStoresStore.create(false)
          .then(() => {
            this.traceAdminStoresStore.clearAdminStoreCreateParameters()

            this.search()
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
    prettyData(store: AdminStore): string {
      try {
        return JSON.stringify(JSON.parse(store.store_data), null, 2)
      } catch {
        return store.store_data
      }
    },
    fillTitle() {
      this.traceAdminStoresStore.createParameters.title = this.traceAdminStoresStore.generateStoreTitle()
    },
  },
})
</script>

<style scoped>
.toolbar-row {
  padding-bottom: 10px;
}

.is-hidden {
  visibility: hidden;
}

.preset-name-input :deep(.el-input-group__append) {
  padding: 0;
}

.append-buttons {
  display: flex;
}

.append-buttons :deep(.el-button) {
  margin: 0;
  border-radius: 0;
}
</style>
