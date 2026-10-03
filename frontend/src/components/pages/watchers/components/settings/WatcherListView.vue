<template>
  <el-table
      :data="items"
      :border="true"
      v-loading="loading"
  >
    <el-table-column label="Name" prop="name" min-width="180"/>
    <el-table-column label="Type" min-width="160">
      <template #default="scope">
        {{ typeTitles[scope.row.type] ?? scope.row.type }}
      </template>
    </el-table-column>
    <el-table-column label="Enabled" width="100">
      <template #default="scope">
        <el-tag :type="scope.row.enabled ? 'success' : 'info'">
          {{ scope.row.enabled ? 'yes' : 'no' }}
        </el-tag>
      </template>
    </el-table-column>
    <el-table-column label="Wait between alerts, sec" prop="cooldown_seconds" width="140"/>
    <el-table-column label="Notifies" min-width="140">
      <template #default="scope">
        <!-- A watcher that tells nobody looks like one that does until you open it. -->
        <el-text v-if="scope.row.notification_channel_id === null" type="info">
          nobody
        </el-text>
        <template v-else>
          {{ channelNames[scope.row.notification_channel_id] ?? `Channel #${scope.row.notification_channel_id}` }}
        </template>
      </template>
    </el-table-column>
    <el-table-column label="Sends" min-width="180">
      <template #default="scope">
        <template v-if="scope.row.notification_channel_id !== null">
          <el-tag v-if="scope.row.notify_on_opened" type="danger">opened</el-tag>
          <el-tag v-if="scope.row.notify_on_event" type="warning">further</el-tag>
          <el-tag v-if="scope.row.notify_on_closed" type="success">closed</el-tag>
        </template>
      </template>
    </el-table-column>
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
    <el-table-column v-if="showActions" width="140" fixed="right">
      <template #default="scope">
        <el-button
            type="primary"
            link
            :disabled="editableTypes.indexOf(scope.row.type) === -1"
            @click="$emit('edit', scope.row)"
        >
          Edit
        </el-button>
        <el-button
            type="danger"
            link
            :loading="deleting[scope.row.id]"
            @click="$emit('delete', scope.row)"
        >
          Delete
        </el-button>
      </template>
    </el-table-column>
  </el-table>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import type {Watcher} from "../../store/watchersStore.ts";

export default defineComponent({
  props: {
    items: {
      type: Array as PropType<Array<Watcher>>,
      required: true,
    },
    typeTitles: {
      type: Object as PropType<Record<string, string>>,
      required: true,
    },
    channelNames: {
      type: Object as PropType<Record<number, string>>,
      required: true,
    },
    editableTypes: {
      type: Array as PropType<Array<string>>,
      default: () => [],
    },
    deleting: {
      type: Object as PropType<Record<number, boolean>>,
      default: () => ({}),
    },
    loading: {
      type: Boolean,
      default: false,
    },
    showActions: {
      type: Boolean,
      default: true,
    },
  },

  emits: ['edit', 'delete'],
})
</script>
