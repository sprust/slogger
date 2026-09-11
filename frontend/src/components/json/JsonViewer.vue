<template>
  <div class="json-viewer">
    <el-row class="json-viewer-toolbar" align="middle">
      <el-space>
        <el-button size="small" :icon="IconCopy" @click="copyValue(value)">
          Copy
        </el-button>
        <el-button size="small" :icon="IconExpand" @click="expandAll">
          Expand all
        </el-button>
        <el-button size="small" :icon="IconCollapse" @click="collapseAll">
          Collapse all
        </el-button>
      </el-space>
    </el-row>
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
import {CopyDocument as IconCopy, Expand as IconExpand, Fold as IconCollapse} from '@element-plus/icons-vue'
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

  computed: {
    IconCopy() {
      return IconCopy
    },
    IconExpand() {
      return IconExpand
    },
    IconCollapse() {
      return IconCollapse
    },
  },

  methods: {
    isCollapsed(path: string, depth: number): boolean {
      return this.overrides[path] ?? depth >= this.openDepth
    },
    toggle(path: string, depth: number) {
      this.overrides[path] = !this.isCollapsed(path, depth)
    },
    expandAll() {
      this.openDepth = Infinity
      this.overrides = {}
    },
    collapseAll() {
      this.openDepth = 1
      this.overrides = {}
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

.json-viewer-toolbar {
  flex: none;
  padding-bottom: 10px;
}

.json-viewer-body {
  flex: 1;
  min-height: 0;
  overflow: auto;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 13px;
  line-height: 20px;
}
</style>
