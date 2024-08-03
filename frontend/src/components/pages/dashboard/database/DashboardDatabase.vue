<template>
  <div style="width: 100%">
    <el-row>
      <el-space>
        <div>
          Databases
        </div>
        <el-button
            :loading="store.state.loading"
            :icon="store.state.loading ? Loading : Refresh"
            link
            @click="update"
        />
      </el-space>
    </el-row>
    <el-divider></el-divider>
    <el-row>
      <el-space wrap>
        <el-card v-for="database in store.state.items">
          <template #header>
            {{ database.name }} (size: {{ `${database.size}mb` }}, memory usage: {{ `${database.memory_usage}mb` }})
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
      </el-space>
    </el-row>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useDashboardDatabaseStore} from "../../../../store/dashboardDatabaseStore.ts";
import {Loading, Refresh} from '@element-plus/icons-vue'

export default defineComponent({
  data() {
    return {
      store: useDashboardDatabaseStore(),
    }
  },
  computed: {
    Loading() {
      return Loading
    },
    Refresh() {
      return Refresh
    }
  },
  methods: {
    update() {
      this.store.dispatch('findDashboardDatabase')
    }
  },
  mounted() {
    if (!this.store.state.loading) {
      return
    }

    this.update()
  },
})

</script>

<style scoped>

</style>
