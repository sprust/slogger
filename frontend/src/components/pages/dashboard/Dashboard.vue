<script lang="ts">
import {defineComponent} from "vue";
import DashboardDatabase from "./DashboardDatabase.vue";
import DashboardMetrics from "./DashboardMetrics.vue";
import Sconcur from "../sconcur/Sconcur.vue";
import TraceCleaner from "../trace-cleaner/TraceCleaner.vue";
import {dashboardTabs, useDashboardTabsStore} from "./store/dashboardTabsStore.ts";

export default defineComponent({
  components: {
    DashboardDatabase,
    DashboardMetrics,
    Sconcur,
    TraceCleaner,
  },

  computed: {
    dashboardTabs() {
      return dashboardTabs
    },
    tabsStore() {
      return useDashboardTabsStore()
    },
  },
})

</script>

<template>
  <el-tabs v-model="tabsStore.currentTab" class="dashboard">
    <el-tab-pane label="Metrics" :name="dashboardTabs.metrics" lazy>
      <DashboardMetrics/>
    </el-tab-pane>
    <el-tab-pane label="Databases" :name="dashboardTabs.databases" lazy>
      <DashboardDatabase/>
    </el-tab-pane>
    <el-tab-pane label="Sconcur" :name="dashboardTabs.sconcur" lazy>
      <Sconcur/>
    </el-tab-pane>
    <el-tab-pane label="Cleaner" :name="dashboardTabs.cleaner" lazy>
      <TraceCleaner/>
    </el-tab-pane>
  </el-tabs>
</template>

<style scoped>
.dashboard {
  padding: 5px;
}
</style>
