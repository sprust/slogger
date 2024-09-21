<template>
  <el-container class="common-layout">
    <el-header>
      <Header v-if="authStore.state.user"/>
    </el-header>
    <div class="height-100" style="padding: 0 20px 20px 20px; overflow-y: auto">
      <router-view/>
    </div>
  </el-container>
</template>

<script lang="ts">

import {defineComponent} from "vue";
import {useAuthStore} from "./store/authStore.ts";
import Header from "./components/Header.vue";

export default defineComponent({
  components: {Header},
  data() {
    return {
      loading: true,
      authStore: useAuthStore(),
    }
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
