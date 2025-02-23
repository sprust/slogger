<template>
  <el-container class="dashboard">
    <DashboardDatabase/>
  </el-container>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import DashboardDatabase from "./DashboardDatabase.vue";

export default defineComponent({
  components: {DashboardDatabase}
})

</script>

<style scoped>
.dashboard {
  padding: 5px;
}
</style>
