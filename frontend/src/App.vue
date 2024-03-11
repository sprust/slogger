<template>
  <div class="common-layout">
    <el-container>
      <el-header>
        <Header v-if="authStore.state.user"/>
      </el-header>
      <el-container>
        <el-main>
          <router-view/>
        </el-main>
      </el-container>
    </el-container>
  </div>
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
})

</script>

<style scoped>

</style>
