<template>
  <div class="json-viewer">
    <div class="json-viewer-body">
      <JsonViewerNode
          :value="value"
          :name="name"
          path=""
          :depth="0"
          :last="true"
          :is-index="false"
      />
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import JsonViewerNode from './JsonViewerNode.vue'
import {copyToClipboard} from '../../utils/helpers.ts'
import alerts from '../../utils/alerts.ts'

export type JsonViewerContext = {
  isCollapsed: (path: string, depth: number) => boolean,
  toggle: (path: string, depth: number) => void,
  copyValue: (value: unknown) => void,
}

export default defineComponent({
  components: {JsonViewerNode},

  props: {
    value: {
      type: null as unknown as PropType<unknown>,
      required: true,
    },
    name: {
      type: String as PropType<string | null>,
      default: null,
    },
    expandDepth: {
      type: Number,
      default: Infinity,
    },
  },

  provide() {
    return {
      jsonViewer: {
        isCollapsed: this.isCollapsed,
        toggle: this.toggle,
        copyValue: this.copyValue,
      } as JsonViewerContext,
    }
  },

  data() {
    return {
      openDepth: this.expandDepth,
      overrides: {} as Record<string, boolean>,
    }
  },

  methods: {
    isCollapsed(path: string, depth: number): boolean {
      return this.overrides[path] ?? depth >= this.openDepth
    },
    toggle(path: string, depth: number) {
      this.overrides[path] = !this.isCollapsed(path, depth)
    },
    copyValue(value: unknown) {
      copyToClipboard(JSON.stringify(value, null, 2) ?? String(value))
          .then(() => alerts.success('Copied'))
    },
  },

  watch: {
    value() {
      this.openDepth = this.expandDepth
      this.overrides = {}
    },
    expandDepth(depth: number) {
      this.openDepth = depth
    },
  },
})
</script>

<style scoped>
.json-viewer {
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.json-viewer-body {
  flex: 1;
  min-height: 0;
  overflow: auto;
}
</style>
