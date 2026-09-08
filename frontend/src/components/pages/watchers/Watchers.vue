<template>
  <el-tabs v-model="tab">
    <el-tab-pane label="Incidents" name="incidents">
      <Incidents/>
    </el-tab-pane>
    <el-tab-pane label="Settings" name="settings">
      <WatcherList/>
    </el-tab-pane>
  </el-tabs>
</template>

<script lang="ts">
import {defineAsyncComponent, defineComponent} from 'vue'

const Incidents = defineAsyncComponent(() => import("./components/incidents/Incidents.vue"))
const WatcherList = defineAsyncComponent(() => import("./components/settings/WatcherList.vue"))

export default defineComponent({
  components: {Incidents, WatcherList},

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
