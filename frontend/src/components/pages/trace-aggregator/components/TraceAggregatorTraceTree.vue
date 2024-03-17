<template>
  <el-progress
      v-if="store.state.loading"
      status="success"
      :text-inside="true"
      :percentage="100"
      :indeterminate="true"
      :duration="5"
      striped
  />
  <el-row v-else-if="store.state.treeNodes.length">
    <el-row style="width: 100%; padding-bottom: 10px">
      <el-space>
        <el-button @click="update">
          Update
        </el-button>
        <div>
          {{ store.state.parameters.traceId }}
        </div>
      </el-space>
    </el-row>
    <el-row style="width: 100%">
      <el-col :span="12" class="row-col" style="padding: 10px">
        <el-tree
            :data="tree"
            :props="treeProps"
            node-key="key"
            default-expand-all
            :expand-on-click-node="false"
        >
          <template #default="{ data }">
            <el-row
                class="tree-row"
                :style="makeTreeNodeStyle(data)"
                @click="onClickOnRow(treeNodeViewsMap[data.key])"
                style="display: contents;"
            >
              <el-space spacer=":">
                <TraceService :name="treeNodeViewsMap[data.key].service?.name"/>
                <div>
                  <el-tag type="success">
                    {{ treeNodeViewsMap[data.key].type }}
                  </el-tag>
                </div>
                <div v-if="treeNodeViewsMap[data.key].tags.length">
                  <el-tag v-for="tag in treeNodeViewsMap[data.key].tags" type="warning">
                    {{ tag.slice(0, 40) }}
                  </el-tag>
                </div>
              </el-space>
              <div class="flex-grow"/>
              <el-space spacer="|">
                <div>
                  {{ treeNodeViewsMap[data.key].duration }}
                </div>
                <div>
                  {{ treeNodeViewsMap[data.key].status }}
                </div>
                <div>
                  {{ convertDateStringToLocal(treeNodeViewsMap[data.key].logged_at) }}
                </div>
              </el-space>
            </el-row>
          </template>
        </el-tree>
      </el-col>
      <el-col :span="12" class="row-col">
        <div v-if="store.state.selectedTrace.trace_id || store.state.dataLoading">
          <el-progress
              v-if="store.state.dataLoading"
              status="success"
              :text-inside="true"
              :percentage="100"
              :indeterminate="true"
              :duration="5"
              striped
          />
          <TraceDetail v-else :trace="store.state.selectedTrace"/>
        </div>
      </el-col>
    </el-row>
  </el-row>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {
  TraceAggregatorTreeNode,
  useTraceAggregatorTreeStore
} from "../../../../store/traceAggregatorTreeStore.ts";
import TraceMetrics from "../widgets/TraceMetrics.vue";
import TraceService from "../widgets/TraceService.vue";
import TraceAggregatorTraceDataNode from "./TraceAggregatorTraceDataNode.vue";
import TraceDetail from "../widgets/TraceDetail.vue";
import {convertDateStringToLocal} from "../../../../utils/helpers.ts";

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
      treeNodeViewsMap: {} as TreeNodeViewsMap
    }
  },
  computed: {
    tree(): Array<TreeNodeView> {
      return this.treeNodesToViews(this.store.state.treeNodes)
    },
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
      const style: { color?: string, 'font-weight'?: string } = {}

      if (data.key === this.store.state.selectedTrace.trace_id) {
        style.color = 'green'
      }

      if (data.key === this.store.state.parameters.traceId) {
        style['font-weight'] = 'bold'
      }

      return style
    }
  }
})
</script>

<style scoped>
.tree-row {
  width: 100%;
  border-bottom: 1px solid rgba(0, 0, 0, .1);;
  padding: 2px;
  cursor: pointer;
}

.flex-grow {
  flex-grow: 1;
}

.row-col {
  height: 85vh;
  overflow-y: scroll
}

</style>
