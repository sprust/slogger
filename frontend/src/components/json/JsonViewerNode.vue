<template>
  <div v-if="expandable">
    <div class="json-line" :style="indent">
      <span class="json-toggle" @click="toggle">
        <el-icon>
          <CaretRight v-if="collapsed"/>
          <CaretBottom v-else/>
        </el-icon>
      </span>
      <template v-if="hasName">
        <span :class="isIndex ? 'json-index' : 'json-key'">{{ name }}</span>
        <span class="json-punct">: </span>
      </template>
      <span class="json-punct">{{ openBracket }}</span>
      <template v-if="collapsed">
        <span class="json-ellipsis" @click="toggle">…</span>
        <span class="json-punct">{{ closeBracket }}{{ comma }}</span>
        <span class="json-hint">{{ hint }}</span>
      </template>
      <el-button class="json-copy" type="info" size="small" link @click="copy">
        copy
      </el-button>
    </div>
    <template v-if="!collapsed">
      <JsonViewerNode
          v-for="(entry, index) in entries"
          :key="entry.key"
          :value="entry.value"
          :name="entry.key"
          :path="childPath(entry.key)"
          :depth="depth + 1"
          :last="index === entries.length - 1"
          :is-index="isArray"
      />
      <div class="json-line" :style="indent">
        <span class="json-toggle-placeholder"/>
        <span class="json-punct">{{ closeBracket }}{{ comma }}</span>
      </div>
    </template>
  </div>
  <div v-else class="json-line" :style="indent">
    <span class="json-toggle-placeholder"/>
    <template v-if="hasName">
      <span :class="isIndex ? 'json-index' : 'json-key'">{{ name }}</span>
      <span class="json-punct">: </span>
    </template>
    <span :class="valueClass">{{ valueText }}<span class="json-punct">{{ comma }}</span></span>
  </div>
</template>

<script lang="ts">
import {defineComponent, PropType} from 'vue'
import {CaretBottom, CaretRight} from '@element-plus/icons-vue'
import type {JsonViewerContext} from './JsonViewer.vue'

type JsonEntry = {
  key: string,
  value: unknown,
}

export default defineComponent({
  name: 'JsonViewerNode',

  components: {CaretBottom, CaretRight},

  inject: {
    jsonViewer: {
      from: 'jsonViewer',
    },
  },

  props: {
    value: {
      type: null as unknown as PropType<unknown>,
      required: true,
    },
    name: {
      type: [String, Number] as PropType<string | number | null>,
      default: null,
    },
    path: {
      type: String,
      required: true,
    },
    depth: {
      type: Number,
      required: true,
    },
    last: {
      type: Boolean,
      required: true,
    },
    isIndex: {
      type: Boolean,
      required: true,
    },
  },

  computed: {
    viewer(): JsonViewerContext {
      return this.jsonViewer as JsonViewerContext
    },
    isArray(): boolean {
      return Array.isArray(this.value)
    },
    isObject(): boolean {
      return this.value !== null && typeof this.value === 'object'
    },
    entries(): Array<JsonEntry> {
      if (Array.isArray(this.value)) {
        return this.value.map((value: unknown, index: number) => ({key: String(index), value}))
      }

      if (this.isObject) {
        return Object.entries(this.value as Record<string, unknown>).map(([key, value]) => ({key, value}))
      }

      return []
    },
    expandable(): boolean {
      return this.isObject && this.entries.length > 0
    },
    collapsed(): boolean {
      return this.viewer.isCollapsed(this.path, this.depth)
    },
    hasName(): boolean {
      return this.name !== null
    },
    openBracket(): string {
      return this.isArray ? '[' : '{'
    },
    closeBracket(): string {
      return this.isArray ? ']' : '}'
    },
    comma(): string {
      return this.last ? '' : ','
    },
    hint(): string {
      const count = this.entries.length

      if (this.isArray) {
        return `${count} ${count === 1 ? 'item' : 'items'}`
      }

      return `${count} ${count === 1 ? 'key' : 'keys'}`
    },
    indent(): Record<string, string> {
      return {'padding-left': `${this.depth * 16}px`}
    },
    valueClass(): string {
      if (this.value === null || this.value === undefined) {
        return 'json-null'
      }

      if (this.isObject) {
        return 'json-punct'
      }

      return `json-${typeof this.value}`
    },
    valueText(): string {
      if (this.value === undefined) {
        return 'undefined'
      }

      if (this.isObject) {
        return this.isArray ? '[]' : '{}'
      }

      return JSON.stringify(this.value)
    },
  },

  methods: {
    toggle() {
      this.viewer.toggle(this.path, this.depth)
    },
    copy() {
      this.viewer.copyValue(this.value)
    },
    childPath(key: string): string {
      return `${this.path}/${key.replace(/~/g, '~0').replace(/\//g, '~1')}`
    },
  },
})
</script>

<style scoped>
.json-line {
  display: flex;
  align-items: flex-start;
  white-space: pre-wrap;
  word-break: break-all;
}

.json-toggle,
.json-toggle-placeholder {
  flex: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 20px;
}

.json-toggle {
  cursor: pointer;
  color: var(--el-text-color-secondary);
}

.json-key {
  flex: none;
  color: var(--el-color-danger);
}

.json-index {
  flex: none;
  color: var(--el-text-color-secondary);
}

.json-punct {
  flex: none;
  color: var(--el-text-color-regular);
}

.json-string {
  color: var(--el-color-success);
}

.json-number,
.json-bigint {
  color: var(--el-color-warning);
}

.json-boolean {
  color: var(--el-color-primary);
}

.json-null {
  color: var(--el-text-color-placeholder);
  font-style: italic;
}

.json-ellipsis {
  cursor: pointer;
  padding: 0 4px;
  color: var(--el-text-color-secondary);
}

.json-hint {
  flex: none;
  margin-left: 8px;
  font-size: 12px;
  color: var(--el-text-color-secondary);
}

.json-copy {
  visibility: hidden;
  margin-left: 8px;
  height: 20px;
}

.json-line:hover > .json-copy {
  visibility: visible;
}
</style>
