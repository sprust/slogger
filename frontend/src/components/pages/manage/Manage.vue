<template>
  <el-tabs v-model="tabsStore.currentTab">
    <el-tab-pane label="Users" :name="manageTabs.users" lazy>
      <Users/>
    </el-tab-pane>
    <el-tab-pane label="Services" :name="manageTabs.services" lazy>
      <Services/>
    </el-tab-pane>
    <el-tab-pane label="Settings" :name="manageTabs.settings" lazy>
      <Settings/>
    </el-tab-pane>
  </el-tabs>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {manageTabs, useManageTabsStore} from "./store/manageTabsStore.ts";
import Users from "../users/Users.vue";
import Services from "../services/Services.vue";
import Settings from "../settings/Settings.vue";

export default defineComponent({
  components: {Users, Services, Settings},

  computed: {
    manageTabs() {
      return manageTabs
    },
    tabsStore() {
      return useManageTabsStore()
    },
  },
})
</script>
