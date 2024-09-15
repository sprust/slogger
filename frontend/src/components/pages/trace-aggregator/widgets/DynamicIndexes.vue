<template>
  <el-space>
    <el-badge
        :value="visibleCount"
        :type="badgeType"
        :offset="[-80, 0]"
    >
      <el-button style="width: 80px" @click="dialogVisible = true">
        Indexes
      </el-button>
    </el-badge>
  </el-space>

  <el-dialog
      v-model="dialogVisible"
      width="80%"
      top="10px"
      :append-to-body="true"
  >
    <template #header>
      <el-text size="default">
        Dynamic indexes (last 100)
      </el-text>
    </template>
    <el-table
        :data="store.state.traceDynamicIndexes"
        style="height: 80vh; width: 100%"
    >
      <el-table-column label="Fields">
        <template #default="props">
          {{ makeName(props.row) }}
        </template>
      </el-table-column>
      <el-table-column label="Status">
        <template #default="props">
          <el-text :type="makeStatusType(props.row)">
            {{ makeStatus(props.row) }}
          </el-text>
        </template>
      </el-table-column>
      <el-table-column label="Created at">
        <template #default="props">
          {{ props.row.createdAt }}
        </template>
      </el-table-column>
      <el-table-column fixed="right" min-width="15">
        <template #default="props">
          <el-button
              size="small"
              type="danger"
              link
              @click="deleteIndex(props.row)"
              :loading="deleting[props.row.id]"
          >
            Delete
          </el-button>
        </template>
      </el-table-column>
    </el-table>
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {TraceDynamicIndex, useTraceDynamicIndexesStore} from "../../../../store/traceDynamicIndexesStore.ts";

interface DeletingIndexes {
  [key: string]: boolean,
}

export default defineComponent({
  data() {
    return {
      dialogVisible: false,
      store: useTraceDynamicIndexesStore(),
      deleting: {} as DeletingIndexes,
    }
  },

  methods: {
    update() {
      this.store.dispatch('findTraceDynamicIndexes')
          .finally(() =>
              setTimeout(() => this.update(), 2000)
          )
    },
    deleteIndex(index: TraceDynamicIndex) {
      if (!confirm('Do you want delete index?')) {
        return
      }

      this.deleting[index.id] = true

      this.store.dispatch('deleteTraceDynamicIndex', {id: index.id})
          .then(() => {
            delete this.deleting[index.id]
          })
    },
    makeName(index: TraceDynamicIndex): string {
      return index.fields.map(index => index.title).join(', ')
    },
    makeStatus(index: TraceDynamicIndex): string {
      if (index.error) {
        return index.error
      }

      if (index.inProcess) {
        return 'In process'
      }

      return 'active'
    },
    makeStatusType(index: TraceDynamicIndex): 'warning' | 'danger' | 'info' {
      if (index.inProcess) {
        return 'warning'
      }

      if (index.error) {
        return 'danger'
      }

      return 'info'
    },
  },

  computed: {
    inProcessCount(): number {
      return this.store.state.traceDynamicIndexes.filter(
          (index: TraceDynamicIndex) => index.inProcess
      ).length
    },
    errorsCount(): number {
      return this.store.state.traceDynamicIndexes.filter(
          (index: TraceDynamicIndex) => !!index.error
      ).length
    },
    totalCount(): number {
      return this.store.state.traceDynamicIndexes.length
    },
    visibleCount(): number {
      if (this.inProcessCount) {
        return this.inProcessCount
      }

      if (this.errorsCount) {
        return this.errorsCount
      }

      return this.totalCount
    },
    badgeType(): 'warning' | 'danger' | 'info' {
      if (this.inProcessCount) {
        return 'warning'
      }

      if (this.errorsCount) {
        return 'danger'
      }

      return 'info'
    },
  },

  mounted() {
    if (!this.store.state.loaded) {
      this.update()
    }
  }
})
</script>

<style scoped>

</style>
