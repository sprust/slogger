<script lang="ts">
import {defineComponent} from "vue";
import DashboardDatabase from "./DashboardDatabase.vue";
import DashboardSconcur from "./DashboardSconcur.vue";

export default defineComponent({
  components: {
    DashboardDatabase,
    DashboardSconcur,
  },

  data() {
    return {
      activeTab: 'database',
    }
  },
})

</script>

<template>
  <el-container class="dashboard">
    <el-tabs v-model="activeTab" class="dashboard-tabs">
      <el-tab-pane label="Database" name="database">
        <DashboardDatabase/>
      </el-tab-pane>
      <el-tab-pane label="Sconcur" name="sconcur" lazy>
        <DashboardSconcur/>
      </el-tab-pane>
    </el-tabs>
  </el-container>
</template>

<style scoped>
.dashboard {
  padding: 5px;
}

.dashboard-tabs {
  width: 100%;
}
</style>
