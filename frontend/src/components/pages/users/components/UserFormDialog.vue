<template>
  <el-dialog
      :model-value="modelValue"
      width="500px"
      :close-on-click-modal="false"
      @update:model-value="$emit('update:modelValue', $event)"
      @open="reset"
  >
    <template #header>
      <el-text>
        {{ user ? 'Rename user' : 'New user' }}
      </el-text>
    </template>

    <el-form label-width="100px" autocomplete="off" @submit.prevent="save">
      <el-form-item label="First name" for="" required>
        <el-input v-model="firstName" maxlength="255" autocomplete="off"/>
      </el-form-item>
      <el-form-item label="Last name" for="">
        <el-input v-model="lastName" maxlength="255" autocomplete="off"/>
      </el-form-item>
      <template v-if="!user">
        <el-form-item label="Email" for="" required>
          <el-input
              v-model="email"
              name="new-user-email"
              maxlength="255"
              placeholder="user@example.com"
              autocomplete="off"
          />
        </el-form-item>
        <el-form-item label="Password" for="" required>
          <el-input
              v-model="password"
              type="password"
              name="new-user-password"
              autocomplete="new-password"
              minlength="8"
              maxlength="10"
              placeholder="8-10 characters"
              show-password
          />
        </el-form-item>
      </template>
    </el-form>

    <template #footer>
      <el-button @click="$emit('update:modelValue', false)">
        Cancel
      </el-button>
      <el-button
          type="primary"
          :loading="saving"
          :disabled="!valid"
          @click="save"
      >
        Save
      </el-button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {User, useUsersStore} from "../store/usersStore.ts";

export default defineComponent({
  emits: ['update:modelValue'],

  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
    user: {
      type: Object as PropType<User | null>,
      required: false,
      default: null,
    },
  },

  data() {
    return {
      firstName: '',
      lastName: '',
      email: '',
      password: '',
      saving: false,
    }
  },

  computed: {
    usersStore() {
      return useUsersStore()
    },
    valid(): boolean {
      if (this.firstName.trim() === '') {
        return false
      }

      if (this.user) {
        return true
      }

      return this.email.trim() !== '' && this.password.length >= 8 && this.password.length <= 10
    },
  },

  methods: {
    reset() {
      this.firstName = this.user?.first_name ?? ''
      this.lastName = this.user?.last_name ?? ''
      this.email = ''
      this.password = ''
    },
    async save() {
      if (!this.valid) {
        return
      }

      const firstName = this.firstName.trim()
      const lastName = this.lastName.trim() === '' ? null : this.lastName.trim()

      this.saving = true

      try {
        const saved = this.user
            ? await this.usersStore.rename(this.user.id, {first_name: firstName, last_name: lastName})
            : await this.usersStore.create({
              first_name: firstName,
              last_name: lastName,
              email: this.email.trim(),
              password: this.password,
            })

        if (saved) {
          this.$emit('update:modelValue', false)
        }
      } finally {
        this.saving = false
      }
    },
  },
})
</script>
