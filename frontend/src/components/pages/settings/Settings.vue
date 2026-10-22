<template>
  <el-space wrap style="margin-bottom: 10px">
    <el-button
        :icon="IconRefresh"
        :loading="settingsStore.loading"
        @click="settingsStore.findSettings()"
    />
    <el-text type="info">
      A value set here wins over .env. Reset goes back to the .env value. A change works without a restart.
    </el-text>
  </el-space>

  <el-table
      :data="settingsStore.items"
      :border="true"
      row-key="key"
      v-loading="settingsStore.loading"
      empty-text="No settings"
  >
    <el-table-column label="Setting" min-width="320">
      <template #default="scope">
        <div class="setting-name">
          <el-text tag="b">{{ scope.row.env_name }}</el-text>
          <el-text type="info">{{ scope.row.description }}</el-text>
        </div>
      </template>
    </el-table-column>
    <el-table-column label="Value" width="120">
      <template #default="scope">
        <el-text :type="scope.row.is_overridden ? 'primary' : ''">
          {{ scope.row.value }}
        </el-text>
      </template>
    </el-table-column>
    <el-table-column label="Default (.env)" prop="default_value" width="130"/>
    <el-table-column label="Updated (UTC)" width="180">
      <template #default="scope">
        {{ scope.row.updated_at ?? '—' }}
      </template>
    </el-table-column>
    <el-table-column width="140" fixed="right">
      <template #default="scope">
        <el-button
            type="primary"
            link
            @click="editSetting(scope.row)"
        >
          Edit
        </el-button>
        <el-popconfirm
            title="Go back to the .env value?"
            @confirm="settingsStore.reset(scope.row.key)"
        >
          <template #reference>
            <el-button
                type="danger"
                link
                :disabled="!scope.row.is_overridden"
            >
              Reset
            </el-button>
          </template>
        </el-popconfirm>
      </template>
    </el-table-column>
  </el-table>

  <SettingFormDialog
      v-model="dialogVisible"
      :setting="dialogSetting"
  />
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {Refresh as IconRefresh} from '@element-plus/icons-vue'
import {Setting, useSettingsStore} from "./store/settingsStore.ts";
import SettingFormDialog from "./components/SettingFormDialog.vue";

export default defineComponent({
  components: {SettingFormDialog},

  data() {
    return {
      dialogVisible: false,
      dialogSetting: null as Setting | null,
    }
  },

  computed: {
    settingsStore() {
      return useSettingsStore()
    },
    IconRefresh() {
      return IconRefresh
    },
  },

  methods: {
    editSetting(setting: Setting) {
      this.dialogSetting = setting
      this.dialogVisible = true
    },
  },

  mounted() {
    if (!this.settingsStore.loaded) {
      this.settingsStore.findSettings()
    }
  },
})
</script>

<style scoped>
.setting-name {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
}
</style>
