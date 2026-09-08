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
        {{ watcherId ? 'Edit' : 'New' }} watcher — {{ definition?.title ?? type }}
      </el-text>
    </template>

    <el-text v-if="definition" type="info">
      {{ definition.description }}
    </el-text>

    <el-form label-width="180px" style="margin-top: 15px" v-loading="loading">
      <el-form-item label="Name">
        <el-input v-model="form.name" placeholder="A short name, so you know what it is"/>
      </el-form-item>
      <el-form-item label="Enabled">
        <el-switch v-model="form.enabled"/>
      </el-form-item>
      <el-form-item label="Wait between alerts, s">
        <el-input-number v-model="form.cooldownSeconds" :min="1" :max="86400"/>
      </el-form-item>

      <el-form-item
          v-for="field in definition?.fields ?? []"
          :key="field.key"
          :label="field.title"
      >
        <el-input-number
            v-model="form.settings[field.key]"
            :min="0"
            :step="field.value_type === 'float' ? 0.1 : 1"
            :precision="field.value_type === 'float' ? 3 : 0"
        />
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
    </el-form>

    <template #footer>
      <el-button @click="$emit('update:modelValue', false)">
        Cancel
      </el-button>
      <el-button
          type="primary"
          :loading="saving"
          :disabled="form.name.trim() === ''"
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
      form: {
        name: '',
        enabled: true,
        cooldownSeconds: 600,
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
    definition(): WatcherType | undefined {
      return this.watcherTypesStore.byType(this.type)
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

      this.form.name = ''
      this.form.enabled = true
      this.form.cooldownSeconds = definition?.default_cooldown_seconds ?? 600
      this.form.settings = {}
      this.form.filter = {service_ids: [], types: [], tags: []}

      definition?.fields.forEach(field => {
        this.form.settings[field.key] = field.default
      })

      if (this.servicesStore.items.length === 0) {
        this.servicesStore.findServices()
      }

      if (this.watcherId === null) {
        return
      }

      const watcher = this.watchersStore.items.find(item => item.id === this.watcherId)

      if (watcher) {
        this.form.name = watcher.name
        this.form.enabled = watcher.enabled
        this.form.cooldownSeconds = watcher.cooldown_seconds
      }

      this.loading = true

      const settings = await this.watchersStore.findSettings(this.type, this.watcherId)
          .finally(() => {
            this.loading = false
          })

      if (!settings) {
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
        settings: {
          ...this.form.settings,
          ...(this.definition?.has_trace_filter ? {filter: this.form.filter} : {}),
        },
      } as WatcherPayload

      this.saving = true

      const saved = this.watcherId === null
          ? this.watchersStore.create(this.type, payload)
          : this.watchersStore.update(this.type, this.watcherId, payload)

      await saved.finally(() => {
        this.saving = false
      })

      // The list is refreshed by the store as part of saving, so closing is all that is
      // left to do here.
      this.$emit('update:modelValue', false)
    },
  },
})
</script>

<style scoped>
</style>
