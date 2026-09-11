<template>
  <el-tabs v-model="tabsStore.currentTab">
    <!-- lazy: without it both panes mount at once and the page opens with two requests
         for the watchers and two for the types, one pair of which nobody is looking at.

         It only defers the first render, though. Once a pane has been opened it stays
         mounted and is hidden with v-show, so switching tabs neither unmounts anything
         nor reads anything again. -->
    <el-tab-pane label="Incidents" :name="watcherTabs.incidents" lazy>
      <Incidents/>
    </el-tab-pane>
    <el-tab-pane label="Settings" :name="watcherTabs.settings" lazy>
      <WatcherList/>
    </el-tab-pane>
    <el-tab-pane label="Channels" :name="watcherTabs.channels" lazy>
      <ChannelList/>
    </el-tab-pane>
  </el-tabs>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {useWatcherTabsStore, watcherTabs} from "./store/watcherTabsStore.ts";
import Incidents from "./components/incidents/Incidents.vue";
import WatcherList from "./components/settings/WatcherList.vue";
import ChannelList from "./components/notifications/ChannelList.vue";

export default defineComponent({
  components: {Incidents, WatcherList, ChannelList},

  computed: {
    watcherTabs() {
      return watcherTabs
    },
    tabsStore() {
      return useWatcherTabsStore()
    },
  },
})
</script>

<style scoped>
</style>
