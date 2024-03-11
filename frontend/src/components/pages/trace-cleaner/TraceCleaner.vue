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
  <el-table v-else :data="store.state.settings" :border="true" @expandChange="tableExpandChange">
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
          <el-table-column label="Cleared at" prop="cleared_at"/>
          <el-table-column label="Created at" prop="created_at"/>
          <el-table-column label="Updated at" prop="updated_at"/>
        </el-table>
      </template>
    </el-table-column>
    <el-table-column label="Type" prop="type"/>
    <el-table-column label="Days lifetime" prop="days_lifetime"/>
    <el-table-column label="Created at" prop="created_at"/>
    <el-table-column label="Updated at" prop="updated_at"/>
    <el-table-column label="" width="150" fixed="right">
      <template #header>
        <el-button
            type="success"
            :icon="DocumentAdd"
            @click="onCreateSetting"
            circle
        />
      </template>
      <template #default="props">
        <el-space>
          <el-button
              type="primary"
              :icon="Edit"
              @click="onEditSetting(props.row)"
              circle
          />
          <el-button
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
    <el-form>
      <el-form-item label="Type">
        <el-input v-model="editorDialog.type" :disabled="!!editorDialog.id"/>
      </el-form-item>
      <el-form-item label="Days lifetime">
        <el-input-number v-model="editorDialog.daysLifetime"/>
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="onEditorSave">
        {{ editorDialog.id ? 'Update' : 'Create' }}
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
import {DocumentAdd, Edit, Delete} from '@element-plus/icons-vue'
import {ElMessageBox} from "element-plus";

export default defineComponent({
  data() {
    return {
      store: useTraceCleanerStore(),
      editorDialog: {
        visible: false,
        id: null as null | number,
        daysLifetime: 30,
        type: ''
      },
    }
  },
  methods: {
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
    onCreateSetting() {
      this.editorDialog.id = null
      this.editorDialog.daysLifetime = 10
      this.editorDialog.type = ''

      this.editorDialog.visible = true
    },
    onEditSetting(setting: TraceCleanerSettingItem) {
      this.editorDialog.id = setting.id
      this.editorDialog.daysLifetime = setting.days_lifetime
      this.editorDialog.type = setting.type ?? ''

      this.editorDialog.visible = true
    },
    onEditorSave() {
      const settingId = this.editorDialog.id

      if (!settingId) {
        this.store.dispatch('createSetting', {
          daysLifetime: this.editorDialog.daysLifetime,
          type: this.editorDialog.type,
          onSuccess: () => {
            this.update()
            this.editorDialog.visible = false
          }
        });
      } else {
        this.store.dispatch('updateSetting', {
          settingId: settingId,
          daysLifetime: this.editorDialog.daysLifetime,
          type: this.editorDialog.type,
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
  },
  computed: {
    DocumentAdd() {
      return DocumentAdd
    },
    Edit() {
      return Edit
    },
    Delete() {
      return Delete
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

<style scoped>

</style>
