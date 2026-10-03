<template>
  <LandingSection anchor="data-path" :title="text.title" :paragraphs="text.paragraphs">
    <div class="landing-flow">
      <VueFlow
          :nodes="nodes"
          :edges="edges"
          :nodes-draggable="false"
          :nodes-connectable="false"
          :elements-selectable="false"
          :pan-on-drag="false"
          :zoom-on-scroll="false"
          :zoom-on-pinch="false"
          :zoom-on-double-click="false"
          :prevent-scrolling="false"
          :default-viewport="{x: 24, y: 40, zoom: 1}"
      >
        <template #node-landing="props">
          <Handle
              v-for="handle in handles"
              :key="handle.id"
              :id="handle.id"
              :type="handle.type"
              :position="handle.position"
              class="landing-flow-handle"
          />
          <el-card shadow="never" class="landing-flow-node" body-class="landing-flow-node-body">
            <el-text>{{ props.data.label }}</el-text>
          </el-card>
        </template>
      </VueFlow>
    </div>
  </LandingSection>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {Edge, Handle, HandleType, Node, Position, VueFlow} from '@vue-flow/core'
import LandingSection from "./LandingSection.vue";
import type {LandingDataPathText} from "../content/types.ts";
import {landingText} from "../content/locale.ts";

interface FlowHandle {
  id: string,
  type: HandleType,
  position: Position,
}

const sides: Array<Position> = [Position.Top, Position.Right, Position.Bottom, Position.Left]

const flowHandles: Array<FlowHandle> = sides.flatMap((side: Position) => [
  {id: `source-${side}`, type: 'source' as HandleType, position: side},
  {id: `target-${side}`, type: 'target' as HandleType, position: side},
])

function node(id: string, label: string, x: number, y: number): Node {
  return {
    id: id,
    type: 'landing',
    position: {x, y},
    data: {label},
  }
}

function edge(source: string, sourceSide: Position, target: string, targetSide: Position, label: string): Edge {
  return {
    id: `${source}-${target}`,
    source: source,
    target: target,
    sourceHandle: `source-${sourceSide}`,
    targetHandle: `target-${targetSide}`,
    label: label,
    animated: true,
  }
}

export default defineComponent({
  components: {LandingSection, VueFlow, Handle},

  computed: {
    text(): LandingDataPathText {
      return landingText().dataPath
    },
    handles(): Array<FlowHandle> {
      return flowHandles
    },
    nodes(): Array<Node> {
      const labels = this.text.nodes

      return [
        node('client', labels.client, 0, 0),
        node('receiver', labels.receiver, 505, 0),
        node('buffer', labels.buffer, 1010, 0),
        node('transporter', labels.transporter, 1010, 150),
        node('pending', labels.pending, 505, 150),
        node('clickhouse', labels.clickhouse, 1010, 300),
        node('backend', labels.backend, 505, 300),
        node('panel', labels.panel, 0, 300),
      ]
    },
    edges(): Array<Edge> {
      const labels = this.text.edges

      return [
        edge('client', Position.Right, 'receiver', Position.Left, labels.clientReceiver),
        edge('receiver', Position.Right, 'buffer', Position.Left, labels.receiverBuffer),
        edge('buffer', Position.Bottom, 'transporter', Position.Top, labels.transporterBuffer),
        edge('transporter', Position.Left, 'pending', Position.Right, labels.transporterPending),
        edge('transporter', Position.Bottom, 'clickhouse', Position.Top, labels.transporterClickhouse),
        edge('clickhouse', Position.Left, 'backend', Position.Right, labels.backendClickhouse),
        edge('backend', Position.Left, 'panel', Position.Right, labels.panelBackend),
      ]
    },
  },
})
</script>

<style>
@import '@vue-flow/core/dist/style.css';
@import '@vue-flow/core/dist/theme-default.css';
</style>

<style scoped>
.landing-flow {
  height: 444px;
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 4px;
}

.landing-flow-node {
  width: 220px;
  height: 64px;
}

:deep(.landing-flow-node-body) {
  height: 100%;
  box-sizing: border-box;
  padding: 0 12px;
  display: flex;
  align-items: center;
}

.landing-flow-handle {
  opacity: 0;
}

.landing-flow :deep(.vue-flow__pane),
.landing-flow :deep(.vue-flow__node),
.landing-flow :deep(.vue-flow__handle) {
  cursor: default;
}

.landing-flow :deep(.vue-flow__edge),
.landing-flow :deep(.vue-flow__edge *) {
  pointer-events: none;
  cursor: default;
}

:deep(.vue-flow__edge-textbg) {
  fill: var(--el-bg-color);
}

:deep(.vue-flow__edge-text) {
  fill: var(--el-text-color-regular);
  font-size: var(--el-font-size-extra-small);
}
</style>
