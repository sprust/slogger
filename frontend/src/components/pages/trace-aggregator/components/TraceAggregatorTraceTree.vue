<template>
  <div class="height-100">
    <el-progress
        v-if="store.state.loading"
        status="success"
        :text-inside="true"
        :percentage="100"
        :indeterminate="true"
        :duration="5"
        striped
    />
    <div v-else-if="store.state.treeNodes.length" class="height-100">
      <el-row style="width: 100%; padding-bottom: 10px">
        <el-space>
          <el-button @click="update">
            Update
          </el-button>
          <div>
            {{ store.state.parameters.traceId }} ({{ store.state.tracesCount }})
          </div>
        </el-space>
      </el-row>
      <el-row style="padding-bottom: 10px">
        <el-input
            v-model="filterTreeNodeText"
            style="width: 400px; padding-right: 5px"
            placeholder="Filter"
            clearable
        />
        <el-space style="padding-right: 5px">
          <el-select
              v-model="store.state.selectedTraceServiceIds"
              placeholder="Services"
              style="min-width: 200px"
              @change="filterTree"
              clearable
              multiple
          >
            <el-option
                v-for="item in store.state.traceServices"
                :key="item.id"
                :label="item.name"
                :value="item.id"
            />
          </el-select>
        </el-space>
        <el-space>
          <el-select
              v-model="store.state.selectedTraceTypes"
              placeholder="Types"
              style="min-width: 200px"
              @change="filterTree"
              clearable
              multiple
          >
            <el-option
                v-for="type in store.state.traceTypes"
                :key="type"
                :label="type"
                :value="type"
            />
          </el-select>
        </el-space>
        <div class="flex-grow"/>
        <el-text type="info">
          status | logged at | memory | cpu | duration
        </el-text>

      </el-row>
      <el-row style="width: 100%; height: 100%; position: relative;">
        <div class="row-col" style="width: 100%;">
          <el-tree
              ref="traceTreeRef"
              :data="tree"
              :props="treeProps"
              node-key="key"
              default-expand-all
              :expand-on-click-node="false"
              :filter-node-method="filterTreeNode"
          >
            <template #default="{ data }">
              <el-row
                  class="tree-row"
                  style="display: contents;"
              >
                <div class="trace-tree-metric-indicator" :style="makeTraceIndicatorStyle(data)"/>
                <div class="trace-tree-select-indicator" :style="makeTreeNodeStyle(data)"/>
                <el-space spacer=":" @click="onClickOnRow(treeNodeViewsMap[data.key])">
                  <TraceService
                      :name="treeNodeViewsMap[data.key].service?.name"
                  />
                  <div>
                    <el-tag type="success">
                      {{ treeNodeViewsMap[data.key].type }}
                    </el-tag>
                  </div>
                  <div v-if="treeNodeViewsMap[data.key].tags.length">
                    <el-tag v-for="tag in treeNodeViewsMap[data.key].tags" type="warning">
                      {{ tag.slice(0, 100) }}
                    </el-tag>
                  </div>
                </el-space>
                <el-button
                    style="padding-left: 5px"
                    @click="indicate(treeNodeViewsMap[data.key])"
                    type="info"
                    size="small"
                    link
                >
                  indicate
                </el-button>
                <div class="flex-grow"/>
                <el-space spacer="|">
                  <div>
                    {{ treeNodeViewsMap[data.key].status }}
                  </div>
                  <div>
                    {{ convertDateStringToLocal(treeNodeViewsMap[data.key].logged_at) }}
                  </div>
                  <div>
                    {{ treeNodeViewsMap[data.key].memory }}
                  </div>
                  <div>
                    {{ treeNodeViewsMap[data.key].cpu }}
                  </div>
                  <div>
                    {{ treeNodeViewsMap[data.key].duration }}
                  </div>
                </el-space>
              </el-row>
            </template>
          </el-tree>
        </div>
        <div
            v-if="showData"
            class="row-col right-col"
            style="position: absolute; right: 0; width: 50%;"
        >
          <el-progress
              v-if="store.state.dataLoading"
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
            <TraceDetail :trace="store.state.selectedTrace"/>
          </div>
        </div>
      </el-row>
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {
  calcTraceIndicators,
  TraceAggregatorTreeNode,
  useTraceAggregatorTreeStore
} from "../../../../store/traceAggregatorTreeStore.ts";
import TraceMetrics from "../widgets/TraceItemMetrics.vue";
import TraceService from "../widgets/TraceService.vue";
import TraceAggregatorTraceDataNode from "./TraceAggregatorTraceDataNode.vue";
import TraceDetail from "../widgets/TraceDetail.vue";
import {convertDateStringToLocal} from "../../../../utils/helpers.ts";
import {state} from "vue-tsc/out/shared";

type TreeNodeView = {
  key: string,
  label: string,
  children: null | Array<TreeNodeView>,
}

interface TreeNodeViewsMap {
  [key: string]: TraceAggregatorTreeNode;
}

export default defineComponent({
  components: {TraceDetail, TraceAggregatorTraceDataNode, TraceService, TraceMetrics},
  data() {
    return {
      store: useTraceAggregatorTreeStore(),
      treeProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },
      treeNodeViewsMap: {} as TreeNodeViewsMap,
      filterTreeNodeText: '',
    }
  },
  computed: {
    state() {
      return state
    },
    tree(): Array<TreeNodeView> {
      return this.treeNodesToViews(this.store.state.treeNodes)
    },
    showData() {
      return this.store.state.selectedTrace.trace_id || this.store.state.dataLoading
    },
    leftSpan() {
      return this.showData ? 12 : 24
    }
  },
  methods: {
    convertDateStringToLocal,
    update() {
      this.store.dispatch('refreshTree')
    },
    treeNodeTitle(treeNode: TraceAggregatorTreeNode): string {
      return treeNode.type + (treeNode.tags.length ? ` [${treeNode.tags.join(' | ')}]` : '')
    },
    onClickOnRow(treeNode: TraceAggregatorTreeNode) {
      this.store.dispatch('findData', treeNode.trace_id)
    },
    onClickCloseData() {
      this.store.dispatch('resetData')
    },
    treeNodesToViews(treeNodes: Array<TraceAggregatorTreeNode>): Array<TreeNodeView> {
      return treeNodes.map((treeNode: TraceAggregatorTreeNode) => {
        this.treeNodeViewsMap[treeNode.trace_id] = treeNode

        return {
          key: treeNode.trace_id,
          label: this.treeNodeTitle(treeNode),
          // @ts-ignore
          children: this.treeNodesToViews(treeNode.children),
          disabled: false,
        }
      })
    },
    makeTreeNodeStyle(data: TreeNodeView) {
      const style: { 'background-color'?: string, 'border'?: string } = {}

      if (data.key === this.store.state.selectedTrace.trace_id) {
        style['background-color'] = 'red'
      }

      if (data.key === this.store.state.parameters.traceId) {
        style['border'] = '1px solid green'
      }

      return style
    },
    makeTraceIndicatorStyle(data: TreeNodeView) {
      const trace = this.treeNodeViewsMap[data.key]

      let percent = 0

      if (trace.duration && this.store.state.traceIndicatingIds.indexOf(trace.trace_id) !== -1) {
        percent = (trace.duration / this.store.state.traceTotalIndicatorsNumber) * 50
      }

      return {
        width: percent + 'vw',
      }
    },
    filterTree() {
      // @ts-ignore
      this.$refs.traceTreeRef!.filter(this.filterTreeNodeText)
    },
    filterTreeNode(value: string, data: TreeNodeView) {
      if (value && !data.label.includes(value)) {
        return false
      }

      const trace = this.treeNodeViewsMap[data.key]

      if (this.store.state.selectedTraceServiceIds.length) {
        if (!trace.service?.id) {
          return false
        }

        if (this.store.state.selectedTraceServiceIds.indexOf(trace.service.id) === -1) {
          return false
        }
      }

      if (this.store.state.selectedTraceTypes.length
          && this.store.state.selectedTraceTypes.indexOf(trace.type) === -1
      ) {
        return false
      }

      return true
    },
    indicate(treeNode: TraceAggregatorTreeNode) {
      // @ts-ignore
      calcTraceIndicators(this.store.state, [treeNode])
    }
  },
  watch: {
    'filterTreeNodeText'() {
      this.filterTree()
    }
  },
})
</script>

<style scoped>
.tree-row {
  width: 100%;
  border-bottom: 1px solid rgba(0, 0, 0, .1);;
  padding: 2px;
  cursor: pointer;
}

.row-col {
  height: 90%;
  overflow-y: scroll;
}

.right-col {
  background-color: var(--el-drawer-bg-color);
  --el-drawer-bg-color: var(--el-dialog-bg-color, var(--el-bg-color));
}

.flex-grow {
  flex-grow: 1;
}

.trace-tree-select-indicator {
  margin-right: 3px;
  width: 10px;
  height: 10px;
  border-radius: 20px 20px 20px 20px;
}

.trace-tree-metric-indicator {
  position: absolute;
  display: flex;
  background-color: rgb(139, 0, 0, 30%);
  right: 0;
  height: 20px;
}
</style>
