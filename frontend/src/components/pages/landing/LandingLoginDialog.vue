<template>
  <el-dialog
      :model-value="modelValue"
      width="380px"
      :append-to-body="false"
      top="10vh"
      class="login-dialog"
      @opened="focusEmail"
      @update:model-value="(value: boolean) => $emit('update:modelValue', value)"
  >
    <template #header>
      <el-text tag="b">{{ text.title }}</el-text>
    </template>
    <el-form label-position="top" class="login-form" @submit.prevent="login">
      <el-form-item :label="text.email">
        <el-input
            ref="emailInput"
            v-model="email"
            :prefix-icon="Message"
            autocomplete="username"
        />
      </el-form-item>
      <el-form-item :label="text.password">
        <el-input
            v-model="password"
            :prefix-icon="Lock"
            type="password"
            show-password
            autocomplete="current-password"
        />
      </el-form-item>
      <el-button native-type="submit" class="login-submit" :disabled="!validated || loading">
        {{ text.submit }}
      </el-button>
      <div class="login-error">
        <el-text v-if="failed" type="danger">{{ text.invalid }}</el-text>
      </div>
    </el-form>
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent, shallowRef} from "vue";
import {Lock, Message} from '@element-plus/icons-vue'
import {useAuthStore} from "../../../store/authStore.ts";
import {routes} from "../../../utils/router.ts";
import {ApiTokenStorage} from "../../../utils/apiContainer.ts";
import type {LandingLoginDialogText} from "./content/types.ts";
import {landingText} from "./content/locale.ts";

export default defineComponent({
  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
  },

  emits: ['update:modelValue'],

  data() {
    return {
      loading: false,
      email: '',
      password: '',
      failed: false,
      Lock: shallowRef(Lock),
      Message: shallowRef(Message),
    }
  },

  computed: {
    text(): LandingLoginDialogText {
      return landingText().loginDialog
    },
    validated(): boolean {
      return !!this.email && !!this.password
    },
  },

  methods: {
    open() {
      if (ApiTokenStorage.getToken()) {
        this.$router.push({name: routes.traceAggregator.name})

        return
      }

      this.$emit('update:modelValue', true)
    },
    focusEmail() {
      (this.$refs.emailInput as { focus?: () => void } | undefined)?.focus?.()
    },
    async login() {
      if (!this.validated || this.loading) {
        return
      }

      this.loading = true
      this.failed = false

      try {
        const authStore = useAuthStore()

        await authStore.login(this.email, this.password)

        if (authStore.user) {
          await this.$router.push({name: routes.traceAggregator.name})

          this.$emit('update:modelValue', false)
        } else {
          this.failed = true
        }
      } finally {
        this.loading = false
      }
    },
  },
})
</script>

<style scoped>
.login-form {
  padding-top: 4px;
}

.login-submit {
  width: 100%;
  margin-top: 8px;
}

.login-error {
  min-height: 22px;
  padding-top: 8px;
}

.login-form :deep(.el-input__inner:-webkit-autofill),
.login-form :deep(.el-input__inner:-webkit-autofill:hover),
.login-form :deep(.el-input__inner:-webkit-autofill:focus) {
  -webkit-box-shadow: 0 0 0 1000px var(--el-input-bg-color, var(--el-fill-color-blank)) inset;
  -webkit-text-fill-color: var(--el-input-text-color, var(--el-text-color-regular));
  caret-color: var(--el-input-text-color, var(--el-text-color-regular));
  transition: background-color 9999s ease-out 0s;
}
</style>
