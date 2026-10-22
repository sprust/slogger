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
        {{ service ? 'Rename service' : 'New service' }}
      </el-text>
    </template>

    <el-form label-width="80px" @submit.prevent="save">
      <el-form-item label="Name" for="">
        <el-input
            v-model="name"
            maxlength="255"
            placeholder="billing-api"
            show-word-limit
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
          :disabled="name.trim() === ''"
          @click="save"
      >
        Save
      </el-button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {ServiceDetail, useServicesAdminStore} from "../store/servicesAdminStore.ts";
import {TraceAggregatorService} from "../../trace-aggregator/components/services/store/traceAggregatorServicesStore.ts";

export default defineComponent({
  emits: ['update:modelValue', 'created'],

  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
    service: {
      type: Object as PropType<TraceAggregatorService | null>,
      required: false,
      default: null,
    },
  },

  data() {
    return {
      name: '',
      saving: false,
    }
  },

  computed: {
    servicesAdminStore() {
      return useServicesAdminStore()
    },
  },

  methods: {
    reset() {
      this.name = this.service?.name ?? ''
    },
    async save() {
      const name = this.name.trim()

      if (name === '') {
        return
      }

      this.saving = true

      try {
        if (this.service) {
          if (await this.servicesAdminStore.rename(this.service.id, name)) {
            this.$emit('update:modelValue', false)
          }

          return
        }

        const created: ServiceDetail | null = await this.servicesAdminStore.create(name)

        if (created) {
          this.$emit('update:modelValue', false)
          this.$emit('created', created)
        }
      } finally {
        this.saving = false
      }
    },
  },
})
</script>
