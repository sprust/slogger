<template>
  <el-table
      :data="deliveries"
      :border="true"
      v-loading="deliveriesStore.loading[channelId]"
      empty-text="Nothing has been sent through this channel yet"
  >
    <el-table-column label="Created" prop="created_at" width="170"/>
    <el-table-column label="Kind" width="90">
      <template #default="scope">
        <el-tag :type="kindTagType(scope.row.kind)">
          {{ scope.row.kind }}
        </el-tag>
      </template>
    </el-table-column>
    <el-table-column label="Status" width="170">
      <template #default="scope">
        <el-tag v-if="scope.row.sent_at" type="success">
          sent {{ scope.row.sent_at }}
        </el-tag>
        <el-tag v-else-if="scope.row.error" type="danger">
          failed
        </el-tag>
        <el-tag v-else type="info">
          queued
        </el-tag>
      </template>
    </el-table-column>
    <el-table-column label="Error" min-width="200">
      <template #default="scope">
        {{ scope.row.error ?? '' }}
      </template>
    </el-table-column>
    <el-table-column label="Message" min-width="320">
      <template #default="scope">
        <pre class="delivery-text">{{ scope.row.text }}</pre>
      </template>
    </el-table-column>
  </el-table>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {Delivery, useDeliveriesStore} from "./store/deliveriesStore.ts";

export default defineComponent({
  props: {
    channelId: {
      type: Number,
      required: true,
    },
  },

  computed: {
    deliveriesStore() {
      return useDeliveriesStore()
    },
    deliveries(): Array<Delivery> {
      return this.deliveriesStore.items[this.channelId] ?? []
    },
  },

  methods: {
    kindTagType(kind: string): string {
      if (kind === 'closed') {
        return 'success'
      }

      return kind === 'event' ? 'warning' : 'danger'
    },
  },

  mounted() {
    this.deliveriesStore.find(this.channelId)
  },
})
</script>

<style scoped>
.delivery-text {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  font-family: inherit;
  font-size: 12px;
}
</style>
