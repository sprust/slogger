<script lang="ts">
import {defineComponent} from "vue";
import {useDashboardDatabaseStore} from "./store/dashboardDatabaseStore.ts";
import {Loading as IconLoading, Refresh as IconRefresh} from '@element-plus/icons-vue'

export default defineComponent({
  components: {
    IconLoading,
    IconRefresh,
  },

  computed: {
    dashboardDatabaseStore() {
      return useDashboardDatabaseStore()
    },
    IconLoading() {
      return IconLoading
    },
    IconRefresh() {
      return IconRefresh
    },
  },

  methods: {
    update() {
      this.dashboardDatabaseStore.findDashboardDatabase()
    }
  },

  mounted() {
    if (!this.dashboardDatabaseStore.loading) {
      return
    }

    this.update()
  },
})

</script>

<template>
  <div style="width: 100%">
    <el-row>
      <el-space>
        <div>
          Databases
        </div>
        <el-button
            :loading="dashboardDatabaseStore.loading"
            :icon="dashboardDatabaseStore.loading ? IconLoading : IconRefresh"
            link
            @click="update"
        />
      </el-space>
    </el-row>
    <el-divider></el-divider>
    <el-row v-for="database in dashboardDatabaseStore.items">
      <el-card style="width: 100%">
        <template #header>
          {{ database.name }} (count: {{ database.total_documents_count }}, size: {{ `${database.size}mb` }}, memory
          usage: {{ `${database.memory_usage}mb` }})
        </template>
        <el-table :data="database.collections" :border="true">
          <el-table-column type="expand">
            <template #default="props">
              <div>
                <h3>indexes</h3>
                <el-table :data="props.row.indexes" :border="true">
                  <el-table-column label="Name" prop="name"/>
                  <el-table-column label="Size (mb)" prop="size"/>
                  <el-table-column label="Usage" prop="usage"/>
                </el-table>
              </div>
            </template>
          </el-table-column>
          <el-table-column label="Name" prop="name"/>
          <el-table-column label="Size" prop="size"/>
          <el-table-column label="Indexes size (mb)" prop="indexes_size"/>
          <el-table-column label="Total size (mb)" prop="total_size"/>
          <el-table-column label="Count" prop="count"/>
          <el-table-column label="Avg obj size (mb)" prop="avg_obj_size"/>
        </el-table>
      </el-card>
    </el-row>
  </div>
</template>

<style scoped>

</style>
