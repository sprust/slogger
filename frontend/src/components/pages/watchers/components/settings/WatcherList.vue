<template>
  <el-space wrap style="margin-bottom: 10px">
    <el-dropdown trigger="click" @command="createWatcher">
      <el-button type="primary">
        New watcher
      </el-button>
      <template #dropdown>
        <el-dropdown-menu>
          <el-dropdown-item
              v-for="type in watcherTypesStore.items"
              :key="type.type"
              :command="type.type"
          >
            <!-- The description is shown here rather than in the menu itself: five lines
                 of explanation would turn a list of five choices into a wall of text. -->
            <el-tooltip
                :content="type.description"
                placement="right"
                :show-after="500"
            >
              <span class="watcher-type-option">{{ type.title }}</span>
            </el-tooltip>
          </el-dropdown-item>
        </el-dropdown-menu>
      </template>
    </el-dropdown>
    <el-button
        :icon="IconRefresh"
        :loading="watchersStore.loading"
        @click="update"
    />
  </el-space>

  <el-table
      :data="watchersStore.items"
      :border="true"
      v-loading="watchersStore.loading"
  >
    <el-table-column label="Name" prop="name" min-width="180"/>
    <el-table-column label="Type" min-width="160">
      <template #default="scope">
        {{ watcherTypesStore.titleOf(scope.row.type) }}
      </template>
    </el-table-column>
    <el-table-column label="Enabled" width="100">
      <template #default="scope">
        <el-tag :type="scope.row.enabled ? 'success' : 'info'">
          {{ scope.row.enabled ? 'yes' : 'no' }}
        </el-tag>
      </template>
    </el-table-column>
    <el-table-column label="Wait between alerts, s" prop="cooldown_seconds" width="140"/>
    <el-table-column label="Collecting since" min-width="160">
      <template #default="scope">
        {{ scope.row.collect_since ?? '' }}
      </template>
    </el-table-column>
    <el-table-column label="Last check / last alert" min-width="180">
      <template #default="scope">
        {{ scope.row.last_checked_at ?? '' }}
        <br>
        {{ scope.row.last_triggered_at ?? '' }}
      </template>
    </el-table-column>
    <el-table-column width="140" fixed="right">
      <template #default="scope">
        <el-button
            type="primary"
            link
            :disabled="!watcherTypeIsKnown(scope.row.type)"
            @click="editWatcher(scope.row)"
        >
          Edit
        </el-button>
        <el-button
            type="danger"
            link
            :loading="deleting[scope.row.id]"
            @click="deleteWatcher(scope.row)"
        >
          Delete
        </el-button>
      </template>
    </el-table-column>
  </el-table>

  <WatcherFormDialog
      v-model="dialogVisible"
      :type="dialogType"
      :watcher-id="dialogWatcherId"
  />
</template>

<script lang="ts">
import {defineAsyncComponent, defineComponent} from 'vue'
import {Refresh as IconRefresh} from '@element-plus/icons-vue'
import {useWatchersStore, Watcher, watcherTypeIsKnown} from "../../store/watchersStore.ts";
import {useWatcherTypesStore} from "../../store/watcherTypesStore.ts";

const WatcherFormDialog = defineAsyncComponent(() => import("./WatcherFormDialog.vue"))

export default defineComponent({
  components: {WatcherFormDialog},

  data() {
    return {
      dialogVisible: false,
      dialogType: '',
      dialogWatcherId: null as number | null,
      deleting: {} as { [id: number]: boolean },
    }
  },

  computed: {
    watchersStore() {
      return useWatchersStore()
    },
    watcherTypesStore() {
      return useWatcherTypesStore()
    },
    IconRefresh() {
      return IconRefresh
    },
  },

  methods: {
    watcherTypeIsKnown,
    update() {
      this.watchersStore.find()
    },
    createWatcher(type: string) {
      this.dialogType = type
      this.dialogWatcherId = null
      this.dialogVisible = true
    },
    editWatcher(watcher: Watcher) {
      this.dialogType = watcher.type
      this.dialogWatcherId = watcher.id
      this.dialogVisible = true
    },
    deleteWatcher(watcher: Watcher) {
      if (!confirm(`Delete watcher "${watcher.name}"? Its incidents will be deleted too.`)) {
        return
      }

      this.deleting[watcher.id] = true

      this.watchersStore.remove(watcher.id)
          .finally(() => {
            delete this.deleting[watcher.id]
          })
    },
  },

  mounted() {
    if (!this.watcherTypesStore.loaded) {
      this.watcherTypesStore.find()
    }

    this.update()
  },
})
</script>

<style scoped>
/* The whole width of the menu row, so the tooltip answers to the row and not to the few
   letters of its title. */
.watcher-type-option {
  display: block;
  width: 100%;
}
</style>
