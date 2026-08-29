<template>
  <el-container class="common-layout">
    <el-header>
      <Header v-if="authStore.user"/>
    </el-header>
    <!-- el-scrollbar rather than the browser's own overflow, so the main area scrolls
         with the same bar the pages inside it already use (Logs, the trace list). -->
    <el-scrollbar class="height-100" style="padding: 0 20px 20px 20px">
      <router-view/>
    </el-scrollbar>
    <PendingRequestDialog/>
  </el-container>
</template>

<script lang="ts">

import {defineComponent} from "vue";
import {useAuthStore} from "./store/authStore.ts";
import Header from "./components/Header.vue";
import PendingRequestDialog from "./components/PendingRequestDialog.vue";

export default defineComponent({
  components: {Header, PendingRequestDialog},

  data() {
    return {
      loading: true,
    }
  },

  computed: {
    authStore() {
      return useAuthStore()
    },
  },

  mounted() {
    document.title = import.meta.env.VITE_APP_NAME ?? 'SLogger'
  }
})

</script>

<style scoped>
.common-layout {
  width: 100%;
  height: 100%;
}
</style>
