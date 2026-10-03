<template>
  <el-space wrap style="margin-bottom: 10px">
    <el-dropdown trigger="click" @command="createWatcher">
      <el-button type="primary">
        New watcher
      </el-button>
      <template #dropdown>
        <el-dropdown-menu>
          <el-dropdown-item
              v-for="type in creatableTypes"
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

  <WatcherListView
      :items="watchersStore.items"
      :type-titles="typeTitles"
      :channel-names="channelNames"
      :editable-types="editableTypes"
      :deleting="deleting"
      :loading="watchersStore.loading"
      @edit="editWatcher"
      @delete="deleteWatcher"
  />

  <WatcherFormDialog
      v-model="dialogVisible"
      :type="dialogType"
      :watcher-id="dialogWatcherId"
  />
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {Refresh as IconRefresh} from '@element-plus/icons-vue'
import {useWatchersStore, Watcher, watcherTypeIsKnown} from "../../store/watchersStore.ts";
import {useWatcherTypesStore, WatcherType} from "../../store/watcherTypesStore.ts";
import {Channel, useChannelsStore} from "../notifications/store/channelsStore.ts";
import WatcherFormDialog from "./WatcherFormDialog.vue";
import WatcherListView from "./WatcherListView.vue";

export default defineComponent({
  components: {WatcherFormDialog, WatcherListView},

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
    /**
     * Only the types this panel knows how to send.
     *
     * A server ahead of the panel offers types whose endpoint is not in the client, and
     * offering them here meant a filled-in form, a dialog that closed and a list that
     * never changed, with nothing anywhere saying why. Editing already worked this way.
     */
    creatableTypes(): Array<WatcherType> {
      return this.watcherTypesStore.items.filter((type: WatcherType) => watcherTypeIsKnown(type.type))
    },
    watcherTypesStore() {
      return useWatcherTypesStore()
    },
    channelsStore() {
      return useChannelsStore()
    },
    IconRefresh() {
      return IconRefresh
    },
    typeTitles(): Record<string, string> {
      const titles: Record<string, string> = {}

      this.watchersStore.items.forEach((watcher: Watcher) => {
        titles[watcher.type] = this.watcherTypesStore.titleOf(watcher.type)
      })

      return titles
    },
    /** A row can still name a channel that is gone; the view falls back to the id. */
    channelNames(): Record<number, string> {
      const names: Record<number, string> = {}

      this.channelsStore.items.forEach((channel: Channel) => {
        names[channel.id] = channel.name
      })

      return names
    },
    editableTypes(): Array<string> {
      return this.watchersStore.items
          .map((watcher: Watcher) => watcher.type)
          .filter((type: string) => watcherTypeIsKnown(type))
    },
  },

  methods: {
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

    if (!this.channelsStore.loaded) {
      this.channelsStore.find()
    }

    // Once, not on every visit: coming back to the page is not a reason to read the list
    // again, and the Refresh button beside it is there for when it is.
    if (!this.watchersStore.loaded) {
      this.update()
    }
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
