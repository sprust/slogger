<template>
  <el-container>
    <DashboardDatabase/>
  </el-container>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import DashboardDatabase from "./database/DashboardDatabase.vue";

export default defineComponent({
  components: {DashboardDatabase}
})

</script>

<style scoped>

</style>
