<template>
  <div class="service-token" v-loading="!detail">
    <el-text type="info">Unique key</el-text>
    <div class="service-token-value">
      <el-text class="service-token-text">{{ detail?.unique_key ?? '—' }}</el-text>
      <el-button :icon="IconCopy" link :disabled="!detail" @click="copy(detail?.unique_key)"/>
    </div>

    <el-text type="info">API token</el-text>
    <div class="service-token-value">
      <el-text class="service-token-text">{{ detail?.api_token ?? '—' }}</el-text>
      <el-button :icon="IconCopy" link :disabled="!detail" @click="copy(detail?.api_token)"/>
    </div>

    <el-text type="info">Created (UTC)</el-text>
    <div class="service-token-value">
      <el-text class="service-token-text">{{ detail?.created_at ?? '—' }}</el-text>
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {DocumentCopy as IconCopy} from '@element-plus/icons-vue'
import {ServiceDetail} from "../store/servicesAdminStore.ts";
import {copyToClipboard} from "../../../../utils/helpers.ts";
import alerts from "../../../../utils/alerts.ts";

export default defineComponent({
  props: {
    detail: {
      type: Object as PropType<ServiceDetail | null>,
      required: false,
      default: null,
    },
  },

  computed: {
    IconCopy() {
      return IconCopy
    },
  },

  methods: {
    async copy(value: string | undefined) {
      if (!value) {
        return
      }

      await copyToClipboard(value)

      alerts.success('Copied')
    },
  },
})
</script>

<style scoped>
.service-token {
  display: grid;
  grid-template-columns: max-content minmax(0, 1fr);
  align-items: center;
  column-gap: 15px;
  row-gap: 4px;
  padding: 6px 20px;
  text-align: left;
}

.service-token-value {
  display: flex;
  align-items: center;
  min-width: 0;
  min-height: 24px;
  gap: 5px;
}

.service-token-text {
  overflow-wrap: anywhere;
}
</style>
