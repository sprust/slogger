<template>
  <el-progress
      v-if="store.state.loading"
      status="success"
      :text-inside="true"
      :percentage="100"
      :indeterminate="true"
      :duration="5"
      striped
  />
  <el-table
      v-else
      :data="store.state.settings"
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
        <el-table v-else :data="store.state.processes[props.row.id]" :border="true">
          <el-table-column label="Cleared count" prop="cleared_count"/>
          <el-table-column label="Cleared at" prop="cleared_at">
            <template #default="scope">
              {{ scope.row.cleared_at ? convertDateStringToLocal(scope.row.cleared_at, false) : '' }}
            </template>
          </el-table-column>
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
              :icon="DocumentAdd"
              @click="onCreateSetting"
              circle
          />
          <el-button
              type="warning"
              :icon="Refresh"
              @click="onUpdate"
              circle
          />
        </el-space>
      </template>
      <template #default="props">
        <el-space>
          <el-button
              :type="props.row.deleted ? 'info' : 'primary'"
              :icon="props.row.deleted ? RefreshLeft : Edit"
              @click="onEditSetting(props.row)"
              circle
          />
          <el-button
              v-if="!props.row.deleted"
              type="danger"
              :icon="Delete"
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
import {TraceCleanerSettingItem, useTraceCleanerStore} from "../../../store/traceCleanerStore.ts";
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
  methods: {
    convertDateStringToLocal,
    update() {
      this.store.dispatch('findTraceCleanerSettings')
    },
    tableExpandChange(setting: TraceCleanerSettingItem) {
      if (this.isSettingProcessLoaded(setting.id)) {
        return
      }

      this.store.dispatch('findTraceCleanerProcesses', setting.id)
    },
    isSettingProcessLoaded(settingId: number): boolean {
      return !!this.store.state.processes[settingId]
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
        this.store.dispatch('createSetting', {
          daysLifetime: this.editorDialog.daysLifetime,
          type: this.editorDialog.type,
          onlyData: this.editorDialog.onlyData,
          onSuccess: () => {
            this.update()
            this.editorDialog.visible = false
          }
        });
      } else {
        this.store.dispatch('updateSetting', {
          settingId: settingId,
          daysLifetime: this.editorDialog.daysLifetime,
          onlyData: this.editorDialog.onlyData,
          onSuccess: () => {
            this.editorDialog.visible = false
          }
        });
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
            this.store.dispatch('deleteSetting', setting.id)
          })
          .catch(() => {
          })
    },
    settingsRowClassName({row}: {row: TraceCleanerSettingItem}) {
      return row.deleted ? 'deleted-setting': ''
    },
  },
  computed: {
    actionButtonTitle() {
      if (!this.editorDialog.id) {
        return 'Create'
      }

      if (this.editorDialog.deleted) {
        return 'Update & restore'
      }

      return 'Update'
    },
    DocumentAdd() {
      return DocumentAdd
    },
    Edit() {
      return Edit
    },
    Delete() {
      return Delete
    },
    RefreshLeft() {
      return RefreshLeft
    },
    Refresh() {
      return Refresh
    },
  },
  mounted() {
    if (!this.store.state.loading) {
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
