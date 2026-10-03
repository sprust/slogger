<template>
  <div v-loading="loading" style="padding: 0 20px 10px 20px">
    <!-- An incident outlives the watcher that found it, and reading its events takes that
         watcher's type: which numbers an event holds is the type's, and the event does not
         carry it. Deleting a watcher from the panel is soft and leaves the row, so this is
         a watcher whose row is gone altogether. -->
    <el-text v-if="watcherMissing" type="info">
      The watcher behind this incident is gone, so its events cannot be read.
    </el-text>

    <el-text v-else-if="!loading && events.length === 0" type="info">
      No events.
    </el-text>

    <el-table
        v-else-if="events.length > 0"
        :data="events"
        :border="true"
        row-key="id"
        :expand-row-keys="expandedEventIds"
        @expand-change="rememberExpanded"
    >
      <el-table-column type="expand">
        <template #default="props">
          <div style="padding: 0 20px 10px 20px">
            <el-text v-if="!props.row.payload" type="info">
              This event was written in a shape this version cannot read.
            </el-text>
            <el-text v-else-if="groupsOf(props.row).length === 0" type="info">
              This watcher reads counters, not traces, so there is nothing to show here.
            </el-text>
            <el-table
                v-else
                :data="groupsOf(props.row)"
                :border="true"
            >
              <el-table-column width="60" align="center">
                <template #default="scope">
                  <el-tooltip
                      content="Find these traces in the aggregator"
                      placement="right"
                      :show-after="500"
                      :disabled="!canOpenInAggregator"
                  >
                    <el-button
                        type="info"
                        link
                        :icon="IconFilter"
                        :style="{visibility: canOpenInAggregator ? 'visible' : 'hidden'}"
                        @click="$emit('open-in-aggregator', scope.row, props.row)"
                    />
                  </el-tooltip>
                </template>
              </el-table-column>
              <el-table-column label="Service" min-width="120">
                <template #default="scope">
                  {{ serviceName(scope.row.service_id) }}
                </template>
              </el-table-column>
              <el-table-column label="Type" prop="type" min-width="100"/>
              <el-table-column label="Tags" min-width="160">
                <template #default="scope">
                  <el-tag
                      v-for="tag in scope.row.tags"
                      :key="tag"
                      type="info"
                      style="margin-right: 4px"
                  >
                    {{ tag }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column label="Traces" prop="count" width="90"/>
              <el-table-column label="Slowest, sec" width="110">
                <template #default="scope">
                  {{ scope.row.duration_max ?? '' }}
                </template>
              </el-table-column>
              <el-table-column label="Slowest trace" prop="trace_id" min-width="220"/>
              <el-table-column label="Slowest started" min-width="160">
                <template #default="scope">
                  {{ scope.row.trace_logged_at ?? '' }}
                </template>
              </el-table-column>
            </el-table>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="Occurred at" prop="occurred_at" min-width="160"/>
      <!-- A column per field the watcher actually filled in. Every event under one
           incident comes from the same watcher, so the columns are the same all the way
           down and the numbers line up. -->
      <el-table-column
          v-for="column in columns"
          :key="`${column.bucket}.${column.key}`"
          :label="column.title"
          min-width="140"
      >
        <template #default="scope">
          {{ payloadValue(scope.row, column) }}
        </template>
      </el-table-column>
    </el-table>

    <el-button
        v-if="events.length > 0 && !exhausted"
        :loading="loading"
        style="margin-top: 10px"
        @click="$emit('load-more')"
    >
      Show more
    </el-button>
  </div>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import type {WatcherIncidentEvent, WatcherIncidentEventGroup} from "../../store/incidentsStore.ts";
import type {PayloadColumn} from "./incidentEventColumns.ts";
import {Filter as IconFilter} from '@element-plus/icons-vue'

export default defineComponent({
  props: {
    events: {
      type: Array as PropType<Array<WatcherIncidentEvent>>,
      required: true,
    },
    columns: {
      type: Array as PropType<Array<PayloadColumn>>,
      required: true,
    },
    serviceNames: {
      type: Object as PropType<Record<number, string>>,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    exhausted: {
      type: Boolean,
      default: false,
    },
    watcherMissing: {
      type: Boolean,
      default: false,
    },
    expandedEventIds: {
      type: Array as PropType<Array<string>>,
      default: () => [],
    },
    canOpenInAggregator: {
      type: Boolean,
      default: true,
    },
  },

  emits: ['load-more', 'expand-change', 'open-in-aggregator'],

  computed: {
    IconFilter() {
      return IconFilter
    },
  },

  methods: {
    /** Empty for an event stored under a shape the server can no longer read. */
    payloadValue(event: WatcherIncidentEvent, column: PayloadColumn): unknown {
      const payload = event.payload

      return payload ? (payload[column.bucket] as Record<string, unknown>)[column.key] : null
    },
    /** Only the watchers that are about traces carry these; the ones that read counters have no groups at all. */
    groupsOf(event: WatcherIncidentEvent): Array<WatcherIncidentEventGroup> {
      const payload = event.payload

      return payload && 'groups' in payload ? payload.groups : []
    },
    rememberExpanded(_row: WatcherIncidentEvent, expanded: Array<WatcherIncidentEvent>) {
      this.$emit('expand-change', expanded.map(event => event.id))
    },
    serviceName(serviceId: number): string {
      return this.serviceNames[serviceId] ?? `Service #${serviceId}`
    },
  },
})
</script>

<style scoped>
</style>
