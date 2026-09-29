<template>
  <div class="connect">
    <div class="connect-block">
      <el-text tag="b">Token</el-text>
      <div class="connect-row">
        <el-input :model-value="mcp.token" readonly/>
        <el-button :icon="IconCopy" @click="copy(mcp.token)"/>
      </div>
    </div>

    <div class="connect-block">
      <el-text tag="b">Claude Code</el-text>
      <el-text type="info">
        Run it once: the server is added for all your projects.
      </el-text>
      <div class="connect-row">
        <el-input :model-value="command" type="textarea" :rows="2" readonly resize="none"/>
        <el-button :icon="IconCopy" @click="copy(command)"/>
      </div>
    </div>

    <div class="connect-block">
      <el-text tag="b">.mcp.json of a project</el-text>
      <el-text type="info">
        Commit the file, not the token: everyone keeps their own token in {{ tokenVariable }}.
      </el-text>
      <div class="connect-row">
        <el-input :model-value="mcpJson" type="textarea" :rows="11" readonly resize="none"/>
        <el-button :icon="IconCopy" @click="copy(mcpJson)"/>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {DocumentCopy as IconCopy} from '@element-plus/icons-vue'
import {Mcp} from "../store/mcpsStore.ts";
import {McpSettings} from "../../../../store/mcpSettingsStore.ts";
import {copyToClipboard} from "../../../../utils/helpers.ts";
import alerts from "../../../../utils/alerts.ts";

export default defineComponent({
  props: {
    mcp: {
      type: Object as PropType<Mcp>,
      required: true,
    },
    settings: {
      type: Object as PropType<McpSettings>,
      required: true,
    },
  },

  computed: {
    IconCopy() {
      return IconCopy
    },
    serverName(): string {
      return `slogger-${this.settings.server_name}`
    },
    tokenVariable(): string {
      return `SLOGGER_${this.settings.server_name.toUpperCase().replace(/-/g, '_')}_TOKEN`
    },
    command(): string {
      return `claude mcp add --transport http --scope user ${this.serverName} ${this.settings.endpoint_url} `
          + `--header "Authorization: Bearer ${this.mcp.token}"`
    },
    mcpJson(): string {
      return JSON.stringify(
          {
            mcpServers: {
              [this.serverName]: {
                type: 'http',
                url: this.settings.endpoint_url,
                headers: {
                  Authorization: `Bearer \${${this.tokenVariable}}`,
                },
              },
            },
          },
          null,
          2
      )
    },
  },

  methods: {
    async copy(value: string) {
      await copyToClipboard(value)

      alerts.success('Copied')
    },
  },
})
</script>

<style scoped>
.connect {
  display: flex;
  flex-direction: column;
  gap: 15px;
  padding: 10px 20px;
  text-align: left;
}

.connect-block {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 5px;
}

.connect-block > .el-text {
  align-self: flex-start;
}

.connect-row {
  display: flex;
  align-self: stretch;
  gap: 5px;
  align-items: flex-start;
}
</style>
