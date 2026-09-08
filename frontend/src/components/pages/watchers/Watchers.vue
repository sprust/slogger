<template>
  <el-tabs v-model="tab">
    <!-- lazy: without it both panes mount at once and the page opens with two requests
         for the watchers and two for the types, one pair of which nobody is looking at. -->
    <el-tab-pane label="Incidents" name="incidents" lazy>
      <Incidents/>
    </el-tab-pane>
    <el-tab-pane label="Settings" name="settings" lazy>
      <WatcherList/>
    </el-tab-pane>
    <el-tab-pane label="Notifications" name="notifications" lazy>
      <ChannelList/>
    </el-tab-pane>
  </el-tabs>
</template>

<script lang="ts">
import {defineAsyncComponent, defineComponent} from 'vue'

const Incidents = defineAsyncComponent(() => import("./components/incidents/Incidents.vue"))
const WatcherList = defineAsyncComponent(() => import("./components/settings/WatcherList.vue"))
const ChannelList = defineAsyncComponent(() => import("./components/notifications/ChannelList.vue"))

export default defineComponent({
  components: {Incidents, WatcherList, ChannelList},

  data() {
    return {
      // Incidents first: the page is opened because something happened far more often
      // than because a threshold wants changing.
      tab: 'incidents',
    }
  },
})
</script>

<style scoped>
</style>
