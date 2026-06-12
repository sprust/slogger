<template>
  <div class="trace-detail">
    <div class="trace-detail-header">
      <el-input
          v-model="searchStore.query"
          placeholder="search in data"
          clearable
          style="width: 220px"
      />
      <el-switch
          v-model="searchStore.inValues"
          class="data-search-switch"
          inline-prompt
          active-text="values"
          inactive-text="keys"
          :width="70"
      />
      <div class="flex-grow"/>
      <el-tooltip placement="bottom-end" effect="light">
        <template #content>
          <el-form
              label-width="80px"
              label-position="left"
              class="trace-detail-info"
          >
            <el-form-item label="Logged at">
              {{ trace.logged_at }}
            </el-form-item>
            <el-form-item label="Id">
              {{ trace.trace_id }}
            </el-form-item>
            <el-form-item v-if="trace.parent_trace_id" label="parent id">
              {{ trace.parent_trace_id }}
            </el-form-item>
            <el-form-item label="Type">
              <el-tag type="success">
                {{ trace.type }}
              </el-tag>
            </el-form-item>
            <el-form-item v-if="trace.tags.length" label="Tags">
              <el-tag v-for="tag in trace.tags" type="warning">
                {{ tag }}
              </el-tag>
            </el-form-item>
            <el-form-item label="Status">
              {{ trace.status }}
            </el-form-item>
            <el-form-item v-if="trace.duration !== null" label="Duration">
              {{ trace.duration }}
            </el-form-item>
          </el-form>
        </template>
        <el-icon size="large" class="trace-detail-info-icon">
          <Tickets/>
        </el-icon>
      </el-tooltip>
      <slot name="actions"/>
    </div>
    <div class="trace-detail-body">
      <TraceAggregatorTraceDataNode :data="trace.data" :show-toolbar="false"/>
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {TraceAggregatorDetail} from "./store/traceAggregatorDataStore.ts";
import {useTraceAggregatorDataSearchStore} from "./store/traceAggregatorDataSearchStore.ts";
import TraceAggregatorTraceDataNode from "./TraceAggregatorTraceDataNode.vue";
import {Tickets} from '@element-plus/icons-vue'

export default defineComponent({
  components: {TraceAggregatorTraceDataNode, Tickets},

  props: {
    trace: {
      type: Object as PropType<TraceAggregatorDetail>,
      required: true,
    },
  },
  computed: {
    searchStore() {
      return useTraceAggregatorDataSearchStore()
    },
  },
})
</script>

<style scoped>
.trace-detail {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  height: 100%;
}

.trace-detail-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px;
}

.trace-detail-body {
  flex: 1;
  min-height: 0;
  overflow: auto;
  padding: 0 10px 10px;
}

.flex-grow {
  flex-grow: 1;
}

.trace-detail-info-icon {
  cursor: pointer;
}

.trace-detail-info {
  min-width: 280px;
}

.data-search-switch {
  --el-switch-on-color: var(--el-border-color);
  --el-switch-off-color: var(--el-border-color);
}
</style>
