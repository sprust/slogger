<template>
  <div style="overflow-y: scroll; width: 100%; height: 80vh">
    <el-row style="width: 100%; padding-bottom: 10px">
      <el-input
          v-model="filterTreeNodeText"
          style="width: 400px"
          placeholder="Filter"
          clearable
      />
    </el-row>
    <el-table
        :data="treeTable"
        table-layout="auto"
        style="min-width: 85vw; width: 300vw"
        border
    >
      <el-table-column label="Avg">
        <template #default="scope">
          <div :style="makeTreeNodeLineIndicatorStyle(scope.row)">
          </div>
          {{ makeIndicatorValuesRoundedPercent(scope.row) }}
        </template>
      </el-table-column>

      <el-table-column
          v-for="indicatorName in traceAggregatorProfilingStore.showProfilingIndicators"
          :label="indicatorName"
      >
        <template #default="scope">
          {{ findNodeIndicator(indicatorName, scope.row).weight_percent }}
        </template>
      </el-table-column>

      <el-table-column>
        <template #default="scope">
          <el-row :class="isSelectedNode(scope.row) ? 'selected-node' : ''">
            <div :style="{'padding-left': `${scope.row.dept * 20}px`}">
              {{ makeNodeTitle(scope.row) }}
              <el-space spacer="|">
                <el-button type="info" @click="onFilter(scope.row)" link>
                  filter
                </el-button>
                <el-button type="info" @click="onExclude(scope.row)" link>
                  exclude
                </el-button>
                <el-button type="info" @click="onShowFlow(scope.row)" link>
                  flow
                </el-button>
              </el-space>
            </div>
          </el-row>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script lang="ts">
import {defineComponent, StyleValue} from "vue";
import {
  ProfilingNodeDataItem,
  ProfilingTreeNodeV2,
  useTraceAggregatorProfilingStore
} from "./store/traceAggregatorProfilingStore.ts";
import TraceAggregatorProfilingNodeData from "./TraceAggregatorProfilingNodeData.vue";
import TraceAggregatorProfilingNodeMetrics from './TraceAggregatorProfilingNodeMetrics.vue'

export default defineComponent({
  components: {TraceAggregatorProfilingNodeData, TraceAggregatorProfilingNodeMetrics},

  data() {
    return {
      treeProps: {
        key: 'key',
        label: 'label',
        children: 'children',
        disabled: 'disabled',
        primary: 'primary',
      },
      filterTreeNodeText: '',
      collapsedIds: new Array<number>
    }
  },

  computed: {
    traceAggregatorProfilingStore() {
      return useTraceAggregatorProfilingStore()
    },
    treeTable() {
      return this.traceAggregatorProfilingStore.treeTable.filter(
          (item: ProfilingTreeNodeV2) => !item.hide
      )
    }
  },

  methods: {
    makeIndicatorValuesRoundedPercent(node: ProfilingTreeNodeV2) {
      let totalPercent = 0
      let totalCount = 0

      node.data.map((dataItem) => {
        if (!dataItem.value || this.traceAggregatorProfilingStore.showProfilingIndicators.indexOf(dataItem.name) === -1) {
          return 0
        }

        totalPercent += dataItem.weight_percent
        totalCount += 1
      })

      if (totalCount === 0) {
        return 0
      }

      return parseFloat((totalPercent / totalCount).toPrecision(5))
    },
    makeNodeTitle(node: ProfilingTreeNodeV2): string {
      return node.id + ': ' + node.calling + (node.recursionNodeId ? ` ---> ${node.recursionNodeId}` : '')
    },
    findNodeIndicator(indicatorName: string, node: ProfilingTreeNodeV2): ProfilingNodeDataItem {
      const foundDataIndicators = node.data.filter(
          (dataItem: ProfilingNodeDataItem) => dataItem.name === indicatorName
      )

      if (foundDataIndicators.length === 1) {
        return foundDataIndicators[0]
      }

      return {
        name: indicatorName,
        value: 0,
        weight_percent: 0,
      }
    },
    isSelectedNode(node: ProfilingTreeNodeV2): boolean {
      return node.id === this.traceAggregatorProfilingStore.selectedItem?.id
    },
    onShowFlow(node: ProfilingTreeNodeV2) {
      if (node.id === this.traceAggregatorProfilingStore.selectedItem?.id) {
        this.traceAggregatorProfilingStore.setSelectedProfilingItem(null)
      } else {
        this.traceAggregatorProfilingStore.setSelectedProfilingItem(node.primary)
      }
    },
    onFilter(node: ProfilingTreeNodeV2) {
      this.traceAggregatorProfilingStore.setBodyCaller(node.calling)
      this.traceAggregatorProfilingStore.findProfilingWithBody()
    },
    onExclude(node: ProfilingTreeNodeV2) {
      this.traceAggregatorProfilingStore.showExcludedCallerPreviewDialog = true

      this.traceAggregatorProfilingStore.excludedCallerPreview = node.calling
    },
    hideTreeNode(value: string, row: ProfilingTreeNodeV2): boolean {
      if (!value) {
        return false
      }

      const valueNumber = Number(value)

      if (!isNaN(valueNumber)) {
        return row.id !== valueNumber
      }

      return !row.calling.includes(value)
    },
    makeTreeNodeLineIndicatorStyle(data: ProfilingTreeNodeV2): StyleValue {
      const roundedPercent = this.makeIndicatorValuesRoundedPercent(data)

      return {
        'background-color': 'rgba(255, 0, 0, .3)',
        'position': 'absolute',
        'width': (roundedPercent) + 'vw',
        'height': '20px',
        'z-index': -999
      }
    }
  },
  watch: {
    'filterTreeNodeText'(value: string) {
      this.traceAggregatorProfilingStore.treeTable.forEach(
          (item: ProfilingTreeNodeV2) => item.hide = this.hideTreeNode(value, item)
      )
    }
  },
})
</script>

<style scoped>
.selected-node {
  font-weight: bold;
}
</style>
