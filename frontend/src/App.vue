<template>
  <el-container class="common-layout">
    <el-header>
      <Header v-if="authStore.user"/>
    </el-header>
    <!-- el-scrollbar rather than the browser's own overflow, so the main area scrolls
         with the same bar the pages inside it already use (Logs, the trace list).

         view-style is not decoration: el-scrollbar puts the slot inside a __view element
         of its own, and that element carries no height. A page asking for `height: 100%`
         then measures itself against something auto and gets auto back — which is how the
         trace tree came to render nothing at all, its virtual list being a box whose only
         children are positioned absolutely. Height on the view restores what the plain
         div this replaced always gave: a definite height to size against. Content taller
         than it still scrolls, since the view does not clip and the overflow reaches the
         __wrap above it. -->
    <el-scrollbar class="height-100" style="padding: 0 20px 20px 20px" view-style="height: 100%">
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
