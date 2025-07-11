<template>
  <div class="height-100">
    <el-progress
        v-if="traceAggregatorTreeStore.loading"
        status="success"
        :text-inside="true"
        :percentage="100"
        :indeterminate="true"
        :duration="5"
        striped
    />
    <div v-else-if="traceAggregatorTreeStore.treeNodes.length" class="height-100">
      <el-row style="width: 100%; padding-bottom: 10px">
        <el-text>
          {{ traceAggregatorTreeStore.parameters.trace_id }} ({{ traceAggregatorTreeStore.content.count }})
        </el-text>
        <div class="flex-grow"/>
        <el-button @click="fresh" link>
          Fresh
        </el-button>
        <el-button
            @click="update"
            :icon="UpdateIcon"
        >
          Update
        </el-button>
      </el-row>
      <el-row style="padding-bottom: 10px">
        <el-space style="padding-right: 5px">
          <el-select
              v-model="traceAggregatorTreeStore.selectedTraceServiceIds"
              :placeholder="'Services ' + '(' + traceAggregatorTreeStore.content.services.length + ')'"
              style="min-width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              multiple
          >
            <el-option
                v-for="item in traceAggregatorTreeStore.content.services"
                :key="item.id"
                :label="item.name + ' (' + item.traces_count + ')'"
                :value="item.id"
            />
          </el-select>
        </el-space>
        <el-space>
          <el-select
              v-model="traceAggregatorTreeStore.selectedTraceTypes"
              :placeholder="'Types ' + '(' + traceAggregatorTreeStore.content.types.length + ')'"
              style="min-width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              multiple
          >
            <el-option
                v-for="item in traceAggregatorTreeStore.content.types"
                :key="item.name"
                :label="item.name + ' (' + item.traces_count + ')'"
                :value="item.name"
            />
          </el-select>
        </el-space>
        <el-space>
          <el-select
              v-model="traceAggregatorTreeStore.selectedTraceTags"
              :placeholder="'Tags ' + '(' + traceAggregatorTreeStore.content.tags.length + ')'"
              style="min-width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              multiple
          >
            <el-option
                v-for="item in traceAggregatorTreeStore.content.tags"
                :key="item.name"
                :label="item.name + ' (' + item.traces_count + ')'"
                :value="item.name"
            />
          </el-select>
        </el-space>
        <el-space>
          <el-select
              v-model="traceAggregatorTreeStore.selectedTraceStatuses"
              :placeholder="'Statuses ' + '(' + traceAggregatorTreeStore.content.statuses.length + ')'"
              style="min-width: 200px"
              collapse-tags
              :max-collapse-tags="2"
              clearable
              multiple
          >
            <el-option
                v-for="item in traceAggregatorTreeStore.content.statuses"
                :key="item.name"
                :label="item.name + ' (' + item.traces_count + ')'"
                :value="item.name"
            />
          </el-select>
          <el-button @click="applyFilters">
            Apply
          </el-button>
        </el-space>
        <div class="flex-grow"/>
        <el-text type="info">
          status | logged at | memory | cpu | duration
        </el-text>
      </el-row>
      <el-row style="width: 100%; height: 100%; position: relative;">
        <div class="row-col" style="width: 100%;">
          <TraceAggregatorTraceTreeVirtual :items="traceAggregatorTreeStore.tree"/>
        </div>
        <div
            v-if="showData"
            class="row-col right-col"
            style="position: absolute; right: 0; width: 50%;"
        >
          <el-progress
              v-if="traceAggregatorTreeStore.dataLoading"
              status="success"
              :text-inside="true"
              :percentage="100"
              :indeterminate="true"
              :duration="5"
              striped
          />
          <div v-else style="padding: 10px">
            <el-row>
              <el-button @click="onClickCloseData">
                Close
              </el-button>
            </el-row>
            <TraceDetail :trace="traceAggregatorTreeStore.selectedTrace"/>
          </div>
        </div>
      </el-row>
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {useTraceAggregatorTreeStore} from "./store/traceAggregatorTreeStore.ts";
import TraceMetrics from "../traces/TraceItemMetrics.vue";
import TraceService from "../services/TraceService.vue";
import TraceAggregatorTraceDataNode from "../trace/TraceAggregatorTraceDataNode.vue";
import TraceDetail from "../trace/TraceDetail.vue";
import TraceAggregatorTraceTreeVirtual from "./TraceAggregatorTraceTreeVirtual.vue";
import {Refresh as UpdateIcon} from '@element-plus/icons-vue'

export default defineComponent({
  components: {
    TraceDetail,
    TraceAggregatorTraceDataNode,
    TraceService,
    TraceMetrics,
    TraceAggregatorTraceTreeVirtual
  },

  data() {
    return {
      treeProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },
    }
  },
  computed: {
    traceAggregatorTreeStore() {
      return useTraceAggregatorTreeStore()
    },
    showData() {
      return this.traceAggregatorTreeStore.selectedTrace.trace_id || this.traceAggregatorTreeStore.dataLoading
    },
    leftSpan() {
      return this.showData ? 12 : 24
    },
    UpdateIcon() {
      return UpdateIcon
    }
  },

  methods: {
    update() {
      this.traceAggregatorTreeStore.updateTree()
    },
    fresh() {
      this.traceAggregatorTreeStore.freshTree()
    },
    onClickCloseData() {
      this.traceAggregatorTreeStore.resetSelectedTrace()
    },
    applyFilters() {
      this.$refs.traceTreeNodes!.filter()
    }
  },
})
</script>

<style scoped>
.row-col {
  height: 90%;
  overflow-y: auto;
}

.right-col {
  background-color: var(--el-drawer-bg-color);
  --el-drawer-bg-color: var(--el-dialog-bg-color, var(--el-bg-color));
}

.flex-grow {
  flex-grow: 1;
}
</style>
