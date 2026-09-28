<template>
  <el-space wrap style="margin-bottom: 10px">
    <el-button @click="createMcp">
      New connection
    </el-button>
    <el-button
        :icon="IconRefresh"
        :loading="mcpsStore.loading"
        @click="update"
    />
    <el-text type="info">
      LLM clients such as Claude Code connect to this installation as MCP server
      <el-text tag="b">{{ serverName }}</el-text>
      and only read its data. Expand a connection for its token and connect command.
    </el-text>
  </el-space>

  <el-table
      :data="mcpsStore.items"
      :border="true"
      row-key="id"
      v-loading="mcpsStore.loading"
      empty-text="No connections yet. Create one to connect an LLM client."
  >
    <el-table-column type="expand">
      <template #default="scope">
        <McpConnect
            v-if="mcpSettingsStore.settings"
            :mcp="scope.row"
            :settings="mcpSettingsStore.settings"
        />
      </template>
    </el-table-column>
    <el-table-column label="Name" prop="name" min-width="220"/>
    <el-table-column label="Enabled" width="100">
      <template #default="scope">
        <el-switch
            :model-value="scope.row.enabled"
            :disabled="!!busy[scope.row.id]"
            @change="toggleEnabled(scope.row)"
        />
      </template>
    </el-table-column>
    <el-table-column label="Last used (UTC)" width="190">
      <template #default="scope">
        {{ scope.row.last_used_at ?? 'never' }}
      </template>
    </el-table-column>
    <el-table-column width="280" fixed="right">
      <template #default="scope">
        <el-button
            type="primary"
            link
            :disabled="!!busy[scope.row.id]"
            @click="renameMcp(scope.row)"
        >
          Rename
        </el-button>
        <el-button
            type="warning"
            link
            :disabled="!!busy[scope.row.id]"
            @click="regenerateToken(scope.row)"
        >
          Regenerate token
        </el-button>
        <el-button
            type="danger"
            link
            :disabled="!!busy[scope.row.id]"
            @click="deleteMcp(scope.row)"
        >
          Delete
        </el-button>
      </template>
    </el-table-column>
  </el-table>

  <McpFormDialog
      v-model="dialogVisible"
      :mcp="dialogMcp"
  />
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {ElMessageBox} from 'element-plus'
import {Refresh as IconRefresh} from '@element-plus/icons-vue'
import {Mcp, useMcpsStore} from "./store/mcpsStore.ts";
import {useMcpSettingsStore} from "../../../store/mcpSettingsStore.ts";
import McpFormDialog from "./components/McpFormDialog.vue";
import McpConnect from "./components/McpConnect.vue";

export default defineComponent({
  components: {McpFormDialog, McpConnect},

  data() {
    return {
      dialogVisible: false,
      dialogMcp: null as Mcp | null,
      busy: {} as { [id: number]: boolean },
    }
  },

  computed: {
    mcpsStore() {
      return useMcpsStore()
    },
    mcpSettingsStore() {
      return useMcpSettingsStore()
    },
    serverName(): string {
      const name = this.mcpSettingsStore.settings?.server_name

      return name ? `slogger-${name}` : ''
    },
    IconRefresh() {
      return IconRefresh
    },
  },

  methods: {
    update() {
      this.mcpsStore.find()
    },
    createMcp() {
      this.dialogMcp = null
      this.dialogVisible = true
    },
    renameMcp(mcp: Mcp) {
      this.dialogMcp = mcp
      this.dialogVisible = true
    },
    async toggleEnabled(mcp: Mcp) {
      this.busy[mcp.id] = true

      await this.mcpsStore.update(mcp.id, mcp.name, !mcp.enabled)
          .finally(() => {
            delete this.busy[mcp.id]
          })
    },
    async regenerateToken(mcp: Mcp) {
      const confirmed = await ElMessageBox.confirm(
          `Regenerate the token of "${mcp.name}"? Clients connected with the current token stop working at once.`,
          'Regenerate token',
          {type: 'warning', confirmButtonText: 'Regenerate', cancelButtonText: 'Cancel'}
      ).then(() => true).catch(() => false)

      if (!confirmed) {
        return
      }

      this.busy[mcp.id] = true

      await this.mcpsStore.regenerateToken(mcp.id)
          .finally(() => {
            delete this.busy[mcp.id]
          })
    },
    async deleteMcp(mcp: Mcp) {
      const confirmed = await ElMessageBox.confirm(
          `Delete connection "${mcp.name}"? Clients using it stop working at once.`,
          'Delete connection',
          {type: 'warning', confirmButtonText: 'Delete', cancelButtonText: 'Cancel'}
      ).then(() => true).catch(() => false)

      if (!confirmed) {
        return
      }

      this.busy[mcp.id] = true

      await this.mcpsStore.remove(mcp.id)
          .finally(() => {
            delete this.busy[mcp.id]
          })
    },
  },

  mounted() {
    if (!this.mcpSettingsStore.loaded) {
      this.mcpSettingsStore.find()
    }

    if (!this.mcpsStore.loaded) {
      this.update()
    }
  },
})
</script>
