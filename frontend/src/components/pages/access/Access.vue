<template>
  <el-tabs v-model="tabsStore.currentTab">
    <el-tab-pane label="Users" :name="accessTabs.users" lazy>
      <Users/>
    </el-tab-pane>
    <el-tab-pane label="Services" :name="accessTabs.services" lazy>
      <Services/>
    </el-tab-pane>
  </el-tabs>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {accessTabs, useAccessTabsStore} from "./store/accessTabsStore.ts";
import Users from "../users/Users.vue";
import Services from "../services/Services.vue";

export default defineComponent({
  components: {Users, Services},

  computed: {
    accessTabs() {
      return accessTabs
    },
    tabsStore() {
      return useAccessTabsStore()
    },
  },
})
</script>
