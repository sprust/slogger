<template>
  <div class="login-container">
    <div>
      <h1>Sign In</h1>
      <el-form>
        <el-form-item>
          <el-input v-model="email" placeholder="Email"></el-input>
        </el-form-item>
        <el-form-item>
          <el-input v-model="password" placeholder="Password" type="password"></el-input>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="login" :disabled="loading || !validated">Login</el-button>
        </el-form-item>
      </el-form>
      <el-alert
          v-if="loginText"
          title="Invalid login"
          type="error"
          :description="loginText"
      />
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useAuthStore} from "../store/authStore.ts";
import {defaultRouteName, router} from "../utils/router.ts";

export default defineComponent({
  data() {
    return {
      loading: false,
      email: '',
      password: '',
      loginText: '',
      store: useAuthStore(),
      router: router,
    };
  },

  computed: {
    authStore() {
      return useAuthStore()
    },
    validated(): boolean {
      return !!this.email && !!this.password
    },
  },

  methods: {
    login() {
      this.loginText = '';

      if (!this.email || !this.password) {
        return;
      } else {
        this.loading = true

        this.authStore.login(this.email, this.password)
            .then(() => {
              try {
                this.router.push({name: defaultRouteName})
              } catch (error) {
                console.log(error)
              }
            })
            .finally(() => {
              this.loading = false
            })
      }
    },
  },
})

</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
