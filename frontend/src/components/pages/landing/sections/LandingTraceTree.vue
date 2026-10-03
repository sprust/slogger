<template>
  <LandingSection anchor="trace-tree" :title="text.title" :paragraphs="text.paragraphs">
    <p class="landing-caption">
      <el-text type="info">{{ text.demoCaption }}</el-text>
    </p>
    <el-card shadow="never" body-class="landing-tree-body">
      <div class="landing-tree">
        <TraceAggregatorTraceTreeVirtual :items="visibleNodes">
          <template #row="{row}">
            <TraceTreeRowView
                :row="row"
                :service-name="serviceName(row.primary.service_id)"
                :selected="row.id === selectedId"
                :highlighted="row.id === highlightedId"
                @toggle-collapse="toggleCollapse(row)"
                @select="select(row)"
                @find-tree="highlightedId = row.id"
                @show-json="jsonNodes = [row]"
                @indicate="indicate(row)"
            />
          </template>
        </TraceAggregatorTraceTreeVirtual>
        <div v-if="selectedTrace" class="right-col">
          <TraceDetailView
              :trace="selectedTrace"
              :search-query="searchQuery"
              :search-in-values="searchInValues"
              @update:search-query="(value: string) => searchQuery = value"
              @update:search-in-values="(value: boolean) => searchInValues = value"
          >
            <template #actions>
              <el-button @click="selectedId = null">
                Close
              </el-button>
            </template>
          </TraceDetailView>
        </div>
      </div>
    </el-card>

    <el-dialog
        v-model="jsonDialogVisible"
        width="80%"
        top="10px"
        :append-to-body="false"
        destroy-on-close
    >
      <JsonViewer
          v-if="jsonNodes"
          :value="jsonValue"
          style="height: 80vh"
      />
    </el-dialog>
  </LandingSection>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import LandingSection from "./LandingSection.vue";
import TraceAggregatorTraceTreeVirtual
  from "../../trace-aggregator/components/tree/TraceAggregatorTraceTreeVirtual.vue";
import TraceTreeRowView from "../../trace-aggregator/components/tree/TraceTreeRowView.vue";
import TraceDetailView from "../../trace-aggregator/components/trace/TraceDetailView.vue";
import JsonViewer from "../../../json/JsonViewer.vue";
import {IndicatorSetter} from "../../trace-aggregator/components/tree/store/IndicatorSetter.ts";
import {TreeJsonBuilder} from "../../trace-aggregator/components/tree/store/TreeJsonBuilder.ts";
import type {TraceTreeNode} from "../../trace-aggregator/components/tree/store/traceAggregatorTreeStore.ts";
import type {TraceAggregatorDetail} from "../../trace-aggregator/components/trace/store/traceAggregatorDataStore.ts";
import type {LandingTraceTreeText} from "../content/types.ts";
import {landingText} from "../content/locale.ts";
import {demoServiceNames, demoTraceData, makeDemoTree, visibleTreeNodes} from "../demo/demoTraceTree.ts";
import {DemoJson, toTraceDetailData} from "../demo/demoTraces.ts";

export default defineComponent({
  components: {LandingSection, TraceAggregatorTraceTreeVirtual, TraceTreeRowView, TraceDetailView, JsonViewer},

  data() {
    return {
      nodes: makeDemoTree(),
      selectedId: null as string | null,
      searchQuery: '',
      searchInValues: false,
      highlightedId: makeDemoTree()[0].id,
      jsonNodes: null as Array<TraceTreeNode> | null,
    }
  },

  computed: {
    text(): LandingTraceTreeText {
      return landingText().traceTree
    },
    visibleNodes(): Array<TraceTreeNode> {
      return visibleTreeNodes(this.nodes)
    },
    jsonDialogVisible: {
      get(): boolean {
        return this.jsonNodes !== null
      },
      set(visible: boolean) {
        if (!visible) {
          this.jsonNodes = null
        }
      },
    },
    jsonValue(): unknown {
      const services: Record<number, { name: string }> = {}

      Object.entries(demoServiceNames).forEach(([id, name]) => {
        services[Number(id)] = {name}
      })

      return new TreeJsonBuilder(services).build(this.jsonNodes ?? [])
    },
    selectedTrace(): TraceAggregatorDetail | null {
      const node = this.nodes.find((item: TraceTreeNode) => item.id === this.selectedId)

      if (!node) {
        return null
      }

      const row = node.primary

      return {
        service: {id: row.service_id, name: this.serviceName(row.service_id)},
        trace_id: row.trace_id,
        parent_trace_id: row.parent_trace_id,
        type: row.type,
        status: row.status,
        tags: row.tags,
        data: toTraceDetailData((demoTraceData[row.trace_id] ?? {}) as { [key: string]: DemoJson }),
        duration: row.duration,
        memory: row.memory,
        cpu: row.cpu,
        logged_at: row.logged_at,
        created_at: row.logged_at,
        updated_at: row.logged_at,
      }
    },
  },

  created() {
    this.indicate(this.nodes[0])
  },

  methods: {
    serviceName(serviceId: number): string {
      return demoServiceNames[serviceId] ?? `Service #${serviceId}`
    },
    indicate(row: TraceTreeNode) {
      new IndicatorSetter(this.nodes.filter((node: TraceTreeNode) => node.depth === 0), row).fill()
    },
    toggleCollapse(row: TraceTreeNode) {
      row.collapsed = !row.collapsed
    },
    select(row: TraceTreeNode) {
      this.selectedId = row.id
    },
  },
})
</script>

<style scoped>
.landing-caption {
  margin: 16px 0 8px 0;
}

.landing-tree {
  height: 360px;
  position: relative;
}

.right-col {
  position: absolute;
  top: 0;
  right: 0;
  width: 50%;
  height: 100%;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  background-color: var(--el-drawer-bg-color);
  --el-drawer-bg-color: var(--el-dialog-bg-color, var(--el-bg-color));
}

:deep(.landing-tree-body) {
  padding: 10px 12px;
}

</style>
