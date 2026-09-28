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
        {{ mcp ? 'Rename connection' : 'New connection' }}
      </el-text>
    </template>

    <el-form label-width="80px" @submit.prevent="save">
      <el-form-item label="Name" for="">
        <el-input
            v-model="name"
            maxlength="255"
            placeholder="Who or what connects: Claude Code — Alex, CI agent"
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
import {Mcp, useMcpsStore} from "../store/mcpsStore.ts";

export default defineComponent({
  emits: ['update:modelValue', 'created'],

  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
    mcp: {
      type: Object as PropType<Mcp | null>,
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
    mcpsStore() {
      return useMcpsStore()
    },
  },

  methods: {
    reset() {
      this.name = this.mcp?.name ?? ''
    },
    async save() {
      const name = this.name.trim()

      if (name === '') {
        return
      }

      this.saving = true

      try {
        if (this.mcp) {
          if (await this.mcpsStore.update(this.mcp.id, name, this.mcp.enabled)) {
            this.$emit('update:modelValue', false)
          }

          return
        }

        const created = await this.mcpsStore.create(name)

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
