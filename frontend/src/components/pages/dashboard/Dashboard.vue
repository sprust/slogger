<template>
  <el-container class="dashboard">
    <DashboardServiceStat/>
  </el-container>
  <el-container class="dashboard">
    <DashboardDatabase/>
  </el-container>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import DashboardDatabase from "./database/DashboardDatabase.vue";
import DashboardServiceStat from "./database/DashboardServiceStat.vue";

export default defineComponent({
  components: {DashboardDatabase, DashboardServiceStat}
})

</script>

<style scoped>
.dashboard {
  padding: 5px;
}
</style>
