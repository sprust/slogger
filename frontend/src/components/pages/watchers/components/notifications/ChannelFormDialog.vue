<template>
  <el-dialog
      :model-value="modelValue"
      width="600px"
      :close-on-click-modal="false"
      @update:model-value="$emit('update:modelValue', $event)"
      @open="fill"
  >
    <template #header>
      <el-text>
        {{ channelId ? 'Edit' : 'New' }} channel — {{ definition?.title ?? type }}
      </el-text>
    </template>

    <el-text v-if="definition" type="info">
      {{ definition.description }}
    </el-text>

    <el-alert
        v-if="loadFailed"
        title="The settings of this channel could not be read. Close the dialog and try again."
        type="error"
        :closable="false"
        show-icon
        style="margin-top: 10px"
    />

    <el-form label-width="180px" style="margin-top: 15px" v-loading="loading">
      <el-form-item label="Name">
        <el-input v-model="form.name" placeholder="A short name, so you know what it is"/>
      </el-form-item>
      <el-form-item label="Enabled">
        <el-switch v-model="form.enabled"/>
      </el-form-item>

      <el-form-item
          v-for="field in definition?.fields ?? []"
          :key="field.key"
          :label="field.title"
      >
        <el-input
            v-model="form.settings[field.key]"
            :maxlength="field.max_length"
            :placeholder="placeholderOf(field)"
            show-word-limit
        />
        <el-text type="info" size="small">{{ field.description }}</el-text>
      </el-form-item>

      <el-divider content-position="left">
        <el-text type="info">What to send</el-text>
      </el-divider>
      <el-form-item label="Incident opened">
        <el-switch v-model="form.onOpened"/>
      </el-form-item>
      <el-form-item label="Further events">
        <el-switch v-model="form.onEvent"/>
      </el-form-item>
      <el-form-item label="Incident closed">
        <el-switch v-model="form.onClosed"/>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="$emit('update:modelValue', false)">
        Cancel
      </el-button>
      <el-button
          type="primary"
          :loading="saving"
          :disabled="!canSave"
          @click="save"
      >
        Save
      </el-button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {ChannelPayload, useChannelsStore} from "./store/channelsStore.ts";
import {ChannelType, ChannelTypeField, useChannelTypesStore} from "./store/channelTypesStore.ts";

interface SettingsForm {
  [key: string]: string
}

export default defineComponent({
  emits: ['update:modelValue'],

  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
    type: {
      type: String,
      required: true,
    },
    channelId: {
      type: Number as PropType<number | null>,
      required: false,
      default: null,
    },
  },

  data() {
    return {
      loading: false,
      saving: false,
      loadFailed: false,
      masked: {} as { [key: string]: string },
      form: {
        name: '',
        enabled: true,
        onOpened: true,
        onEvent: false,
        onClosed: true,
        settings: {} as SettingsForm,
      },
    }
  },

  computed: {
    channelsStore() {
      return useChannelsStore()
    },
    channelTypesStore() {
      return useChannelTypesStore()
    },
    definition(): ChannelType | undefined {
      return this.channelTypesStore.byType(this.type)
    },
    canSave(): boolean {
      if (this.form.name.trim() === '' || this.loading || this.loadFailed) {
        return false
      }

      return (this.definition?.fields ?? []).every((field: ChannelTypeField) => {
        if (field.secret && this.channelId !== null) {
          return true
        }

        return (this.form.settings[field.key] ?? '').trim() !== ''
      })
    },
  },

  methods: {
    placeholderOf(field: ChannelTypeField): string {
      if (!field.secret || this.channelId === null) {
        return ''
      }

      const mask = this.masked[field.key] ?? ''

      return mask === '' ? 'Not set' : `${mask} — leave empty to keep it`
    },
    async fill() {
      const channelId = this.channelId

      this.loadFailed = false
      this.masked = {}
      this.form.name = ''
      this.form.enabled = true
      this.form.onOpened = true
      this.form.onEvent = false
      this.form.onClosed = true
      this.form.settings = {}

      this.definition?.fields.forEach(field => {
        this.form.settings[field.key] = ''
      })

      if (channelId === null) {
        return
      }

      const channel = this.channelsStore.items.find(item => item.id === channelId)

      if (channel) {
        this.form.name = channel.name
        this.form.enabled = channel.enabled
        this.form.onOpened = channel.on_opened
        this.form.onEvent = channel.on_event
        this.form.onClosed = channel.on_closed
      }

      this.loading = true

      const settings = await this.channelsStore.findSettings(this.type, channelId)
          .finally(() => {
            if (channelId === this.channelId) {
              this.loading = false
            }
          })

      if (channelId !== this.channelId) {
        return
      }

      if (!settings) {
        this.loadFailed = true

        return
      }

      const raw = settings as Record<string, any>

      this.definition?.fields.forEach(field => {
        if (field.secret) {
          this.masked[field.key] = raw[`${field.key}_mask`] ?? ''

          return
        }

        const value = raw[field.key]

        if (typeof value === 'string') {
          this.form.settings[field.key] = value
        }
      })
    },
    async save() {
      const payload = {
        name: this.form.name.trim(),
        enabled: this.form.enabled,
        on_opened: this.form.onOpened,
        on_event: this.form.onEvent,
        on_closed: this.form.onClosed,
        settings: this.settingsToSend(),
      } as ChannelPayload

      this.saving = true

      const saved = this.channelId === null
          ? this.channelsStore.create(this.type, payload)
          : this.channelsStore.update(this.type, this.channelId, payload)

      const succeeded = await saved.finally(() => {
        this.saving = false
      })

      if (succeeded !== true) {
        return
      }

      this.$emit('update:modelValue', false)
    },
    /**
     * A secret left alone is sent as null: the form was shown a mask, and an empty string
     * would be refused by the schema's minLength.
     */
    settingsToSend(): Record<string, string | null> {
      const settings: Record<string, string | null> = {}

      this.definition?.fields.forEach(field => {
        const value = (this.form.settings[field.key] ?? '').trim()

        settings[field.key] = field.secret && value === '' ? null : value
      })

      return settings
    },
  },
})
</script>

<style scoped>
</style>
