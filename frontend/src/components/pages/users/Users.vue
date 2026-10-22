<template>
  <el-space wrap style="margin-bottom: 10px">
    <el-button @click="createUser">
      New user
    </el-button>
    <el-button
        :icon="IconRefresh"
        :loading="usersStore.loading"
        @click="usersStore.findUsers()"
    />
    <el-text type="info">
      Users sign in to this panel with their email and password.
    </el-text>
  </el-space>

  <el-table
      :data="usersStore.items"
      :border="true"
      row-key="id"
      v-loading="usersStore.loading"
      empty-text="No users"
  >
    <el-table-column label="Id" prop="id" width="90"/>
    <el-table-column label="Name" min-width="200">
      <template #default="scope">
        {{ fullName(scope.row) }}
      </template>
    </el-table-column>
    <el-table-column label="Email" prop="email" min-width="220"/>
    <el-table-column label="Created (UTC)" prop="created_at" width="180"/>
    <el-table-column label="Updated (UTC)" prop="updated_at" width="180"/>
    <el-table-column width="120" fixed="right">
      <template #default="scope">
        <el-button
            type="primary"
            link
            @click="renameUser(scope.row)"
        >
          Rename
        </el-button>
      </template>
    </el-table-column>
  </el-table>

  <UserFormDialog
      v-model="dialogVisible"
      :user="dialogUser"
  />
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {Refresh as IconRefresh} from '@element-plus/icons-vue'
import {User, useUsersStore} from "./store/usersStore.ts";
import UserFormDialog from "./components/UserFormDialog.vue";

export default defineComponent({
  components: {UserFormDialog},

  data() {
    return {
      dialogVisible: false,
      dialogUser: null as User | null,
    }
  },

  computed: {
    usersStore() {
      return useUsersStore()
    },
    IconRefresh() {
      return IconRefresh
    },
  },

  methods: {
    fullName(user: User): string {
      return [user.first_name, user.last_name].filter(Boolean).join(' ')
    },
    createUser() {
      this.dialogUser = null
      this.dialogVisible = true
    },
    renameUser(user: User) {
      this.dialogUser = user
      this.dialogVisible = true
    },
  },

  mounted() {
    if (!this.usersStore.loaded) {
      this.usersStore.findUsers()
    }
  },
})
</script>
