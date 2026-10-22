<template>
  <el-space wrap style="margin-bottom: 10px">
    <el-button @click="createService">
      New service
    </el-button>
    <el-button
        :icon="IconRefresh"
        :loading="servicesStore.loading"
        @click="update"
    />
    <el-text type="info">
      Clients send traces as a service, with its API token. Drag a row by its handle to change
      the order of services in every list. Expand a row for its token.
    </el-text>
  </el-space>

  <el-table
      ref="table"
      :data="servicesStore.items"
      :border="true"
      row-key="id"
      :expand-row-keys="expandedKeys"
      v-loading="servicesStore.loading || sorting"
      empty-text="No services yet. Create one so a client can send traces."
      @expand-change="onExpandChange"
  >
    <el-table-column width="50" align="center">
      <template #default>
        <el-icon class="drag-handle">
          <IconRank/>
        </el-icon>
      </template>
    </el-table-column>
    <el-table-column type="expand">
      <template #default="scope">
        <ServiceToken :detail="servicesAdminStore.details[scope.row.id] ?? null"/>
      </template>
    </el-table-column>
    <el-table-column label="Id" prop="id" width="90"/>
    <el-table-column label="Name" prop="name" min-width="220"/>
    <el-table-column width="120" fixed="right">
      <template #default="scope">
        <el-button
            type="primary"
            link
            @click="renameService(scope.row)"
        >
          Rename
        </el-button>
      </template>
    </el-table-column>
  </el-table>

  <ServiceFormDialog
      v-model="dialogVisible"
      :service="dialogService"
      @created="onCreated"
  />
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import Sortable from 'sortablejs'
import {Rank as IconRank, Refresh as IconRefresh} from '@element-plus/icons-vue'
import {
  TraceAggregatorService,
  useTraceAggregatorServicesStore
} from "../trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";
import {ServiceDetail, useServicesAdminStore} from "./store/servicesAdminStore.ts";
import ServiceFormDialog from "./components/ServiceFormDialog.vue";
import ServiceToken from "./components/ServiceToken.vue";

export default defineComponent({
  components: {ServiceFormDialog, ServiceToken, IconRank},

  data() {
    return {
      dialogVisible: false,
      dialogService: null as TraceAggregatorService | null,
      expandedKeys: [] as Array<string>,
      sorting: false,
      sortable: null as Sortable | null,
      dragNextSibling: null as Node | null,
    }
  },

  computed: {
    servicesStore() {
      return useTraceAggregatorServicesStore()
    },
    servicesAdminStore() {
      return useServicesAdminStore()
    },
    IconRefresh() {
      return IconRefresh
    },
  },

  methods: {
    update() {
      this.servicesStore.findServices()

      Object.keys(this.servicesAdminStore.details).forEach((id: string) => {
        if (this.expandedKeys.includes(id)) {
          this.servicesAdminStore.findDetail(Number(id))
        }
      })
    },
    createService() {
      this.dialogService = null
      this.dialogVisible = true
    },
    renameService(service: TraceAggregatorService) {
      this.dialogService = service
      this.dialogVisible = true
    },
    onCreated(service: ServiceDetail) {
      this.expandedKeys = [...this.expandedKeys, String(service.id)]
    },
    onExpandChange(row: TraceAggregatorService, expandedRows: Array<TraceAggregatorService>) {
      this.expandedKeys = expandedRows.map((service: TraceAggregatorService) => String(service.id))

      const expanded = expandedRows.some((service: TraceAggregatorService) => service.id === row.id)

      if (expanded && !this.servicesAdminStore.details[row.id]) {
        this.servicesAdminStore.findDetail(row.id)
      }
    },
    initSortable() {
      const table = this.$refs.table as { $el: HTMLElement } | undefined
      const body = table?.$el.querySelector('.el-table__body-wrapper tbody') as HTMLElement | null

      if (!body) {
        return
      }

      this.sortable = Sortable.create(body, {
        handle: '.drag-handle',
        draggable: '.el-table__row',
        animation: 150,
        onStart: (event: Sortable.SortableEvent) => {
          this.dragNextSibling = event.item.nextSibling
        },
        onEnd: (event: Sortable.SortableEvent) => {
          event.from.insertBefore(event.item, this.dragNextSibling)

          this.dragNextSibling = null

          this.onSorted(event.oldDraggableIndex, event.newDraggableIndex)
        },
      })
    },
    async onSorted(oldIndex: number | undefined, newIndex: number | undefined) {
      if (oldIndex === undefined || newIndex === undefined || oldIndex === newIndex) {
        return
      }

      const items = [...this.servicesStore.items]
      const [moved] = items.splice(oldIndex, 1)

      items.splice(newIndex, 0, moved)

      this.servicesStore.items = items

      this.sorting = true

      await this.servicesAdminStore.sort(items.map((service: TraceAggregatorService) => service.id))
          .finally(() => {
            this.sorting = false
          })
    },
  },

  mounted() {
    if (!this.servicesStore.loaded) {
      this.update()
    }

    this.initSortable()
  },

  beforeUnmount() {
    this.sortable?.destroy()
  },
})
</script>

<style scoped>
.drag-handle {
  cursor: grab;
}
</style>
