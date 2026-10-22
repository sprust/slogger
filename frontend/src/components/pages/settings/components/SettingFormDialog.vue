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
        {{ setting?.env_name ?? '' }}
      </el-text>
    </template>

    <el-space direction="vertical" alignment="stretch" fill style="width: 100%">
      <el-text type="info">
        {{ setting?.description ?? '' }}
      </el-text>
      <el-form label-width="80px" @submit.prevent="save">
        <el-form-item label="Value" for="">
          <el-input-number
              v-model="value"
              :min="setting?.min ?? 1"
              :max="setting?.max ?? 1"
              :step="1"
              step-strictly
          />
        </el-form-item>
      </el-form>
      <el-text type="info">
        From {{ setting?.min }} to {{ setting?.max }}. Default (.env): {{ setting?.default_value }}.
      </el-text>
      <el-alert
          type="warning"
          :closable="false"
          show-icon
          :style="{visibility: deletesTraces ? 'visible' : 'hidden'}"
          title="Traces older than the new value are deleted by the next hourly cleanup. They can't be restored."
      />
    </el-space>

    <template #footer>
      <el-button @click="$emit('update:modelValue', false)">
        Cancel
      </el-button>
      <el-button
          type="primary"
          :loading="saving"
          :disabled="value === null"
          @click="save"
      >
        Save
      </el-button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {Setting, useSettingsStore} from "../store/settingsStore.ts";

export default defineComponent({
  emits: ['update:modelValue'],

  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
    setting: {
      type: Object as PropType<Setting | null>,
      required: false,
      default: null,
    },
  },

  data() {
    return {
      value: null as number | null,
      saving: false,
    }
  },

  computed: {
    settingsStore() {
      return useSettingsStore()
    },
    deletesTraces(): boolean {
      return this.setting?.key === 'traces_lifetime_hours'
          && this.value !== null
          && this.value < this.setting.value
    },
  },

  methods: {
    reset() {
      this.value = this.setting?.value ?? null
    },
    async save() {
      if (!this.setting || this.value === null) {
        return
      }

      this.saving = true

      try {
        if (await this.settingsStore.update(this.setting.key, this.value)) {
          this.$emit('update:modelValue', false)
        }
      } finally {
        this.saving = false
      }
    },
  },
})
</script>
