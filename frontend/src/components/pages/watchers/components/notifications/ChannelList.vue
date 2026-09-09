<template>
  <el-space wrap style="margin-bottom: 10px">
    <el-dropdown trigger="click" @command="createChannel">
      <el-button type="primary">
        New channel
      </el-button>
      <template #dropdown>
        <el-dropdown-menu>
          <el-dropdown-item
              v-for="type in creatableTypes"
              :key="type.type"
              :command="type.type"
          >
            <el-tooltip
                :content="type.description"
                placement="right"
                :show-after="500"
            >
              <span class="channel-type-option">{{ type.title }}</span>
            </el-tooltip>
          </el-dropdown-item>
        </el-dropdown-menu>
      </template>
    </el-dropdown>
    <el-button
        :icon="IconRefresh"
        :loading="channelsStore.loading"
        @click="update"
    />
  </el-space>

  <el-table
      :data="channelsStore.items"
      :border="true"
      row-key="id"
      v-loading="channelsStore.loading"
      empty-text="No channels yet. Watchers have nowhere to send alerts."
  >
    <el-table-column type="expand">
      <template #default="scope">
        <div class="deliveries">
          <ChannelDeliveries :channel-id="scope.row.id"/>
        </div>
      </template>
    </el-table-column>
    <el-table-column label="Name" prop="name" min-width="180"/>
    <el-table-column label="Type" min-width="120">
      <template #default="scope">
        {{ channelTypesStore.titleOf(scope.row.type) }}
      </template>
    </el-table-column>
    <el-table-column label="Enabled" width="100">
      <template #default="scope">
        <el-tag :type="scope.row.enabled ? 'success' : 'info'">
          {{ scope.row.enabled ? 'yes' : 'no' }}
        </el-tag>
      </template>
    </el-table-column>
    <el-table-column label="Sends" min-width="180">
      <template #default="scope">
        <el-tag v-if="scope.row.on_opened" type="danger">opened</el-tag>
        <el-tag v-if="scope.row.on_event" type="warning">event</el-tag>
        <el-tag v-if="scope.row.on_closed" type="success">closed</el-tag>
      </template>
    </el-table-column>
    <el-table-column label="Test" min-width="220">
      <template #default="scope">
        <el-tag v-if="results[scope.row.id]" :type="results[scope.row.id].delivered ? 'success' : 'danger'">
          {{ resultText(scope.row.id) }}
        </el-tag>
      </template>
    </el-table-column>
    <el-table-column width="200" fixed="right">
      <template #default="scope">
        <el-button
            type="success"
            link
            :loading="testing[scope.row.id]"
            @click="testChannel(scope.row)"
        >
          Test
        </el-button>
        <el-button
            type="primary"
            link
            :disabled="!channelTypeIsKnown(scope.row.type)"
            @click="editChannel(scope.row)"
        >
          Edit
        </el-button>
        <el-button
            type="danger"
            link
            :loading="deleting[scope.row.id]"
            @click="deleteChannel(scope.row)"
        >
          Delete
        </el-button>
      </template>
    </el-table-column>
  </el-table>

  <ChannelFormDialog
      v-model="dialogVisible"
      :type="dialogType"
      :channel-id="dialogChannelId"
  />
</template>

<script lang="ts">
import {defineAsyncComponent, defineComponent} from 'vue'
import {Refresh as IconRefresh} from '@element-plus/icons-vue'
import {Channel, channelTypeIsKnown, SendResult, useChannelsStore} from "./store/channelsStore.ts";
import {ChannelType, useChannelTypesStore} from "./store/channelTypesStore.ts";

const ChannelFormDialog = defineAsyncComponent(() => import("./ChannelFormDialog.vue"))
const ChannelDeliveries = defineAsyncComponent(() => import("./ChannelDeliveries.vue"))

export default defineComponent({
  components: {ChannelFormDialog, ChannelDeliveries},

  data() {
    return {
      dialogVisible: false,
      dialogType: '',
      dialogChannelId: null as number | null,
      deleting: {} as { [id: number]: boolean },
      testing: {} as { [id: number]: boolean },
      results: {} as { [id: number]: SendResult },
    }
  },

  computed: {
    channelsStore() {
      return useChannelsStore()
    },
    channelTypesStore() {
      return useChannelTypesStore()
    },
    creatableTypes(): Array<ChannelType> {
      return this.channelTypesStore.items.filter((type: ChannelType) => channelTypeIsKnown(type.type))
    },
    IconRefresh() {
      return IconRefresh
    },
  },

  methods: {
    channelTypeIsKnown,
    update() {
      this.channelsStore.find()
    },
    resultText(id: number): string {
      const result = this.results[id]

      if (!result) {
        return ''
      }

      return result.delivered ? 'delivered' : (result.error ?? 'failed')
    },
    createChannel(type: string) {
      this.dialogType = type
      this.dialogChannelId = null
      this.dialogVisible = true
    },
    editChannel(channel: Channel) {
      this.dialogType = channel.type
      this.dialogChannelId = channel.id
      this.dialogVisible = true
    },
    async testChannel(channel: Channel) {
      this.testing[channel.id] = true

      const result = await this.channelsStore.test(channel.id)
          .finally(() => {
            delete this.testing[channel.id]
          })

      if (result) {
        this.results[channel.id] = result
      }
    },
    deleteChannel(channel: Channel) {
      if (!confirm(`Delete channel "${channel.name}"?`)) {
        return
      }

      this.deleting[channel.id] = true

      this.channelsStore.remove(channel.id)
          .finally(() => {
            delete this.deleting[channel.id]
          })
    },
  },

  mounted() {
    if (!this.channelTypesStore.loaded) {
      this.channelTypesStore.find()
    }

    this.update()
  },
})
</script>

<style scoped>
.channel-type-option {
  display: block;
  width: 100%;
}

.deliveries {
  padding: 10px 20px;
}
</style>
