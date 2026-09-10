<template>
  <el-dialog
      :model-value="modelValue"
      width="600px"
      :close-on-click-modal="false"
      @update:model-value="$emit('update:modelValue', $event)"
  >
    <template #header>
      <el-text>
        {{ watcherId ? 'Edit' : 'New' }} watcher — {{ definition?.title ?? type }}
      </el-text>
    </template>

    <el-text v-if="definition" type="info">
      {{ definition.description }}
    </el-text>

    <el-alert
        v-if="loadFailed"
        title="The settings of this watcher could not be read. Close the dialog and try again."
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
      <el-form-item label="Wait between alerts, s">
        <el-input-number
            v-model="form.cooldownSeconds"
            :min="1"
            :max="86400"
            :value-on-clear="definition?.default_cooldown_seconds ?? 600"
        />
      </el-form-item>
      <el-form-item label="Notify through">
        <el-select
            v-model="form.notificationChannelId"
            clearable
            placeholder="Nobody — only open an incident"
            style="width: 100%"
        >
          <el-option
              v-for="channel in channelsStore.items"
              :key="channel.id"
              :label="channel.enabled ? channel.name : `${channel.name} (off)`"
              :value="channel.id"
          />
        </el-select>
        <el-text type="info">
          Leave it empty and the watcher opens incidents here without telling anyone.
        </el-text>
      </el-form-item>

      <template v-if="definition?.has_trace_filter">
        <el-divider content-position="left">
          <el-text type="info">
            Which traces to watch. Leave empty to watch all of them.
          </el-text>
        </el-divider>
        <el-form-item label="Services">
          <el-select
              v-model="form.filter.service_ids"
              multiple
              filterable
              clearable
              placeholder="Any service"
              style="width: 100%"
          >
            <el-option
                v-for="service in servicesStore.items"
                :key="service.id"
                :label="service.name"
                :value="service.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Types">
          <el-select
              v-model="form.filter.types"
              multiple
              filterable
              allow-create
              default-first-option
              clearable
              placeholder="Any type"
              style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Tags">
          <el-select
              v-model="form.filter.tags"
              multiple
              filterable
              allow-create
              default-first-option
              clearable
              placeholder="Any tag. A trace matches if it has any one of them."
              style="width: 100%"
          />
        </el-form-item>
      </template>

      <el-divider content-position="left">
        <el-text type="info">
          What sets this watcher off.
        </el-text>
      </el-divider>
      <el-form-item
          v-for="field in definition?.fields ?? []"
          :key="field.key"
          :label="field.title"
         
      >
        <!-- The bounds come from the server's own rules (/watchers/types), so a number it
             would refuse cannot be typed. A rejected save used to close the dialog and
             take the whole form with it. -->
        <el-input-number
            v-model="form.settings[field.key]"
            :min="field.min"
            :max="field.max ?? Infinity"
            :value-on-clear="field.default"
            :step="field.value_type === 'float' ? 0.1 : 1"
            :precision="field.value_type === 'float' ? 3 : 0"
        />
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
import {useWatchersStore, WatcherPayload} from "../../store/watchersStore.ts";
import {useWatcherTypesStore, WatcherType} from "../../store/watcherTypesStore.ts";
import {
  useTraceAggregatorServicesStore
} from "../../../trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";
import {useChannelsStore} from "../../components/notifications/store/channelsStore.ts";

/** The numbers of one watcher, keyed the way `/watchers/types` names them. */
interface SettingsForm {
  [key: string]: number
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
    watcherId: {
      type: Number as PropType<number | null>,
      required: false,
      default: null,
    },
  },

  data() {
    return {
      loading: false,
      saving: false,
      // Whether what is in the form is this watcher's own settings.
      //
      // A failed read is not an empty form: fill() has already put the type's defaults in
      // it, and handleApiRequest answers with undefined instead of throwing. Saving then
      // wrote those defaults — and an empty filter — over a watcher nobody meant to
      // change, without a word on screen.
      loadFailed: false,
      form: {
        name: '',
        enabled: true,
        cooldownSeconds: 600,
        notificationChannelId: null as number | null,
        settings: {} as SettingsForm,
        filter: {
          service_ids: [] as number[],
          types: [] as string[],
          tags: [] as string[],
        },
      },
    }
  },

  computed: {
    watchersStore() {
      return useWatchersStore()
    },
    watcherTypesStore() {
      return useWatcherTypesStore()
    },
    servicesStore() {
      return useTraceAggregatorServicesStore()
    },
    channelsStore() {
      return useChannelsStore()
    },
    definition(): WatcherType | undefined {
      return this.watcherTypesStore.byType(this.type)
    },
    canSave(): boolean {
      return this.form.name.trim() !== '' && !this.loading && !this.loadFailed
    },
  },

  watch: {
    modelValue: {
      immediate: true,
      handler(visible: boolean) {
        if (visible) {
          this.fill()
        }
      },
    },
  },

  methods: {
    /**
     * Puts the form where it should start: the type's defaults for a new watcher, the
     * stored settings for one being edited.
     *
     * Read on opening rather than held: the dialog is the same component whichever
     * watcher it is opened for, and a form still carrying the previous one's numbers is
     * how a threshold gets moved by accident.
     */
    async fill() {
      const definition = this.definition

      // The dialog is one component reused for every watcher, and a response outlives the
      // dialog it was opened for: edit A, close it, edit B, and A's settings would land in
      // B's form — and be saved onto B.
      const watcherId = this.watcherId

      this.loading = false
      this.loadFailed = false
      this.form.name = ''
      this.form.enabled = true
      this.form.cooldownSeconds = definition?.default_cooldown_seconds ?? 600
      this.form.notificationChannelId = null
      this.form.settings = {}
      this.form.filter = {service_ids: [], types: [], tags: []}

      definition?.fields.forEach(field => {
        this.form.settings[field.key] = field.default
      })

      if (this.servicesStore.items.length === 0) {
        this.servicesStore.findServices()
      }

      if (!this.channelsStore.loaded) {
        this.channelsStore.find()
      }

      if (watcherId === null) {
        return
      }

      const watcher = this.watchersStore.items.find(item => item.id === watcherId)

      if (watcher) {
        this.form.name = watcher.name
        this.form.enabled = watcher.enabled
        this.form.cooldownSeconds = watcher.cooldown_seconds
        this.form.notificationChannelId = watcher.notification_channel_id ?? null
      }

      this.loading = true

      const settings = await this.watchersStore.findSettings(this.type, watcherId)
          .finally(() => {
            if (watcherId === this.watcherId) {
              this.loading = false
            }
          })

      if (watcherId !== this.watcherId) {
        return
      }

      if (!settings) {
        this.loadFailed = true

        return
      }

      definition?.fields.forEach(field => {
        const value = (settings as Record<string, any>)[field.key]

        if (typeof value === 'number') {
          this.form.settings[field.key] = value
        }
      })

      this.form.filter = {
        service_ids: settings.filter?.service_ids ?? [],
        types: settings.filter?.types ?? [],
        tags: settings.filter?.tags ?? [],
      }
    },
    async save() {
      const payload = {
        name: this.form.name.trim(),
        enabled: this.form.enabled,
        cooldown_seconds: this.form.cooldownSeconds,
        // element-plus clears a select with undefined, and JSON.stringify drops it —
        // the server wants the key even when it is null.
        notification_channel_id: this.form.notificationChannelId ?? null,
        settings: {
          ...this.form.settings,
          ...(this.definition?.has_trace_filter ? {filter: this.form.filter} : {}),
        },
      } as WatcherPayload

      this.saving = true

      const saved = this.watcherId === null
          ? this.watchersStore.create(this.type, payload)
          : this.watchersStore.update(this.type, this.watcherId, payload)

      const succeeded = await saved.finally(() => {
        this.saving = false
      })

      // Only on success. A rejected save — a 422 the server sends, a request that never
      // arrived — is reported by handleApiRequest and answered with undefined; closing
      // regardless would throw away everything that was typed along with the reason.
      if (succeeded !== true) {
        return
      }

      // The list is refreshed by the store as part of saving, so closing is all that is
      // left to do here.
      this.$emit('update:modelValue', false)
    },
  },
})
</script>

<style scoped>
</style>
