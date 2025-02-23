<template>
  <el-progress
      v-if="traceCleanerStore.loading"
      status="success"
      :text-inside="true"
      :percentage="100"
      :indeterminate="true"
      :duration="5"
      striped
  />
  <el-table
      v-else
      :data="traceCleanerStore.settings"
      :border="true"
      @expandChange="tableExpandChange"
      :row-class-name="settingsRowClassName"
  >
    <el-table-column type="expand">
      <template #default="props">
        <el-progress
            v-if="!isSettingProcessLoaded(props.row.id)"
            status="success"
            :text-inside="true"
            :percentage="100"
            :indeterminate="true"
            :duration="1"
        />
        <el-table v-else :data="traceCleanerStore.settingProcesses[props.row.id]" :border="true">
          <el-table-column label="Cleared count" prop="cleared_count"/>
          <el-table-column label="Created/Updated at" prop="created_at">
            <template #default="scope">
              {{ convertDateStringToLocal(scope.row.created_at, false) }}
              <br>
              {{ convertDateStringToLocal(scope.row.updated_at, false) }}
            </template>
          </el-table-column>
          <el-table-column label="Cleared at" prop="cleared_at">
            <template #default="scope">
              {{ scope.row.cleared_at ? convertDateStringToLocal(scope.row.cleared_at, false) : '' }}
            </template>
          </el-table-column>
          <el-table-column label="Error" prop="error"/>
        </el-table>
      </template>
    </el-table-column>
    <el-table-column label="Type" prop="type"/>
    <el-table-column label="Only data">
      <template #default="scope">
        {{ scope.row.only_data ? 'yes' : '' }}
      </template>
    </el-table-column>
    <el-table-column label="Days lifetime" prop="days_lifetime"/>
    <el-table-column label="Created at" prop="created_at">
      <template #default="scope">
        {{ convertDateStringToLocal(scope.row.created_at, false) }}
      </template>
    </el-table-column>
    <el-table-column label="Updated at" prop="updated_at">
      <template #default="scope">
        {{ convertDateStringToLocal(scope.row.updated_at, false) }}
      </template>
    </el-table-column>
    <el-table-column label="" width="150" fixed="right">
      <template #header>
        <el-space>
          <el-button
              type="success"
              :icon="IconCreate"
              @click="onCreateSetting"
              circle
          />
          <el-button
              type="warning"
              :icon="IconRefreshList"
              @click="onUpdate"
              circle
          />
        </el-space>
      </template>
      <template #default="props">
        <el-space>
          <el-button
              :type="props.row.deleted ? 'info' : 'primary'"
              :icon="props.row.deleted ? IconRestore : IconEdit"
              @click="onEditSetting(props.row)"
              circle
          />
          <el-button
              v-if="!props.row.deleted"
              type="danger"
              :icon="IconDelete"
              @click="onDeleteSetting(props.row)"
              circle
          />
        </el-space>
      </template>
    </el-table-column>
  </el-table>

  <el-dialog
      v-model="editorDialog.visible"
      width="100vh"
      top="10px"
      :append-to-body="true"
  >
    <el-form style="margin-top: 5px">
      <el-form-item label="Type">
        <el-input v-model="editorDialog.type" :disabled="!!editorDialog.id"/>
      </el-form-item>
      <el-form-item label="Days lifetime">
        <el-input-number v-model="editorDialog.daysLifetime"/>
      </el-form-item>
      <el-form-item label="Only data">
        <el-checkbox v-model="editorDialog.onlyData"/>
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="onEditorSave">
        {{ actionButtonTitle }}
      </el-button>
      <el-button @click="editorDialog.visible = false">
        Close
      </el-button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {TraceCleanerSettingItem, useTraceCleanerStore} from "./store/traceCleanerStore.ts";
import {DocumentAdd, Edit, Delete, RefreshLeft, Refresh} from '@element-plus/icons-vue'
import {ElMessageBox} from "element-plus";
import {convertDateStringToLocal} from "../../../utils/helpers.ts";

export default defineComponent({
  data() {
    return {
      store: useTraceCleanerStore(),
      editorDialog: {
        visible: false,
        id: null as null | number,
        daysLifetime: 30,
        type: '',
        onlyData: false,
        deleted: false,
      },
    }
  },

  computed: {
    traceCleanerStore() {
      return useTraceCleanerStore()
    },
    actionButtonTitle() {
      if (!this.editorDialog.id) {
        return 'Create'
      }

      if (this.editorDialog.deleted) {
        return 'Update & restore'
      }

      return 'Update'
    },
    IconCreate() {
      return DocumentAdd
    },
    IconEdit() {
      return Edit
    },
    IconDelete() {
      return Delete
    },
    IconRestore() {
      return RefreshLeft
    },
    IconRefreshList() {
      return Refresh
    },
  },

  methods: {
    convertDateStringToLocal,
    update() {
      this.traceCleanerStore.findTraceCleanerSettings()
    },
    tableExpandChange(setting: TraceCleanerSettingItem) {
      if (this.isSettingProcessLoaded(setting.id)) {
        delete this.traceCleanerStore.settingProcesses[setting.id]

        return
      }

      this.traceCleanerStore.findTraceCleanerProcesses(setting.id)
    },
    isSettingProcessLoaded(settingId: number): boolean {
      return !!this.traceCleanerStore.settingProcesses[settingId]
    },
    onUpdate() {
      this.update()
    },
    onCreateSetting() {
      this.editorDialog.id = null
      this.editorDialog.daysLifetime = 10
      this.editorDialog.type = ''
      this.editorDialog.onlyData = false
      this.editorDialog.deleted = false

      this.editorDialog.visible = true
    },
    onEditSetting(setting: TraceCleanerSettingItem) {
      this.editorDialog.id = setting.id
      this.editorDialog.daysLifetime = setting.days_lifetime
      this.editorDialog.type = setting.type ?? ''
      this.editorDialog.onlyData = setting.only_data
      this.editorDialog.deleted = setting.deleted

      this.editorDialog.visible = true
    },
    onEditorSave() {
      const settingId = this.editorDialog.id

      if (!settingId) {
        this.traceCleanerStore
            .createSetting(
                this.editorDialog.daysLifetime,
                this.editorDialog.type,
                this.editorDialog.onlyData,
            )
            .then(() => {
              this.update()
              this.editorDialog.visible = false
            });
      } else {
        this.traceCleanerStore
            .updateSetting(
                settingId,
                this.editorDialog.daysLifetime,
                this.editorDialog.onlyData,
            )
            .then(() => {
              this.editorDialog.visible = false
            })
      }
    },
    onDeleteSetting(setting: TraceCleanerSettingItem) {
      ElMessageBox
          .confirm(
              `Are you sure? Do you want to delete setting of type [${setting.type ? setting.type : '<all>'}]`,
              'Warning',
              {
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                type: 'warning',
              }
          )
          .then(() => {
            this.traceCleanerStore.deleteSetting(setting.id)
          })
          .catch(() => {
          })
    },
    settingsRowClassName({row}: { row: TraceCleanerSettingItem }) {
      return row.deleted ? 'deleted-setting' : ''
    },
  },

  mounted() {
    if (!this.traceCleanerStore.loading) {
      return
    }

    this.update()
  },
})
</script>

<style>
.deleted-setting {
  color: grey;
}
</style>
