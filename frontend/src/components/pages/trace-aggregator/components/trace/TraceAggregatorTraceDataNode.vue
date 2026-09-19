<template>
  <el-row v-if="showToolbar" class="data-toolbar" align="middle">
    <el-space>
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
      <el-text v-if="matchCount" type="info">
        {{ matchCount }} found
      </el-text>
    </el-space>
  </el-row>

  <el-tree
      ref="treeRef"
      :data="tree"
      :props="treeProps"
      node-key="key"
      :expand-on-click-node="false"
      :filter-node-method="filterNode"
      :default-expanded-keys="expandedKeys"
      class="data-tree"
  >
    <template #default="{ data }">
      <div class="data-row">
        <DataNodeText v-if="isParentData(data)" class="data-branch" :text="data.name" :query="keyQuery"/>
        <template v-else>
          <DataNodeText class="data-key" :text="data.name" :query="keyQuery"/>
          <span class="data-punct">:</span>
          <DataNodeText
              :class="['data-value', valueClass(data)]"
              :text="valueText(data)"
              :query="valueQuery"
              :title="valueTitle(data)"
          />
        </template>
        <span v-if="isParentData(data)" class="data-hint">{{ data.hint }}</span>
        <span class="data-actions">
          <el-space v-if="isParentData(data)" :size="4" spacer="|">
            <el-button type="info" size="small" link @click="onShowNodeJson(data)">
              json
            </el-button>
            <el-button type="info" size="small" link @click="expandBranch(data, true)">
              expand
            </el-button>
            <el-button type="info" size="small" link @click="expandBranch(data, false)">
              collapse
            </el-button>
          </el-space>
          <el-space v-else :size="4" spacer="|">
            <el-button type="info" size="small" link @click="onCustomFieldShow(data)">
              show
            </el-button>
            <el-button type="info" size="small" link @click="onCustomFieldCopy(data)">
              copy
            </el-button>
            <el-button type="info" size="small" link @click="onCopyPath(data)">
              path
            </el-button>
            <el-button v-if="showCustomButton" type="info" size="small" link @click="onCustomFieldFilter(data)">
              custom
            </el-button>
          </el-space>
        </span>
      </div>
    </template>
  </el-tree>

  <el-dialog
      v-model="dataDialog.visible"
      width="80%"
      top="10px"
      :append-to-body="true"
  >
    <el-row style="min-height: 80vh; overflow: auto">
      <pre>{{ dataDialog.data }}</pre>
    </el-row>
  </el-dialog>

  <el-dialog
      v-model="jsonDialog.visible"
      width="80%"
      top="10px"
      :append-to-body="true"
  >
    <JsonViewer :value="jsonDialog.value" style="height: 80vh"/>
  </el-dialog>
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
import {
  TraceAggregatorCustomFieldParameter,
} from "../traces/store/traceAggregatorStore.ts";
import {TraceAggregatorDetailData} from "./store/traceAggregatorDataStore.ts";
import {useTraceAggregatorDataSearchStore} from "./store/traceAggregatorDataSearchStore.ts";
import {copyToClipboard} from "../../../../../utils/helpers.ts";
import DataNodeText from "./DataNodeText.vue";
import FilterTagsSection from "../tags/FilterTagsSection.vue";
import JsonViewer from "../../../../json/JsonViewer.vue";

type TreeNode = {
  key: string,
  name: string,
  label: string,
  value: unknown,
  hint: string,
  children: null | Array<TreeNode>,
  canBeFiltered: boolean,
  disabled: boolean,
}

/**
 * A branch wider than this starts collapsed. The one that always is, is `__trace`: a
 * backtrace nobody opened the trace to read, sitting on top of the payload they did.
 */
const maxExpandedChildren: number = 20

const longValueLength: number = 80

export default defineComponent({
  components: {DataNodeText, FilterTagsSection, JsonViewer},
  emits: ["onCustomFieldClick"],
  props: {
    data: {
      type: Object as PropType<TraceAggregatorDetailData>,
      required: true,
    },
    showCustomButton: {
      type: Boolean,
      required: false,
      default: false
    },
    showToolbar: {
      type: Boolean,
      required: false,
      default: true
    },
  },
  data() {
    return {
      dataValues: {} as any,
      dataKeys: {} as any,
      dataNodes: {} as any,
      dataDialog: {
        visible: false,
        data: null,
      },
      jsonDialog: {
        visible: false,
        value: null as unknown,
      },
      treeProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },
    }
  },
  computed: {
    searchStore() {
      return useTraceAggregatorDataSearchStore()
    },
    tree(): Array<TreeNode> {
      return [this.dataNodeToTree(this.data, 0, 'root')]
    },
    expandedKeys(): Array<string> {
      const keys: Array<string> = []

      this.collectExpandedKeys(this.tree, keys)

      return keys
    },
    keyQuery(): string {
      return this.searchStore.inValues ? '' : this.searchStore.query
    },
    valueQuery(): string {
      return this.searchStore.inValues ? this.searchStore.query : ''
    },
    matchCount(): number {
      const query: string = this.searchStore.query.trim().toLowerCase()

      if (!query) {
        return 0
      }

      return this.countMatches(this.tree, query)
    },
  },
  methods: {
    nodeEndKey(key: string) {
      const keyData: Array<string> = key.split(".");

      return keyData[keyData.length - 1]
    },
    isParentData(data: TreeNode): boolean {
      return !!data.children
    },
    dataNodeToTree(data: TraceAggregatorDetailData, index: number, postNodeKey: string): TreeNode {
      const nodeKey: string = `${postNodeKey}.${index}.${this.nodeEndKey(data.key)}`

      this.dataNodes[nodeKey] = data

      if (!data.children) {
        this.dataValues[nodeKey] = data.value
        this.dataKeys[nodeKey] = data.key

        const name: string = this.nodeEndKey(nodeKey)

        return {
          key: nodeKey,
          name: name,
          label: name + ': ' + data.value,
          value: data.value,
          hint: '',
          children: null,
          canBeFiltered: data.can_be_filtered,
          disabled: false,
        }
      }

      const children: Array<TreeNode> = data.children.map(
          // @ts-ignore
          (child: TraceAggregatorDetailData, childIndex: number): TreeNode => {
            return this.dataNodeToTree(child, childIndex, nodeKey)
          }
      )

      const name: string = data.key ? this.nodeEndKey(data.key) : 'root'

      return {
        key: nodeKey,
        name: name,
        label: name,
        value: null,
        hint: this.makeHint(children),
        children: children,
        canBeFiltered: data.can_be_filtered,
        disabled: true,
      }
    },
    makeHint(children: Array<TreeNode>): string {
      const isArray: boolean = children.every(
          (child: TreeNode, index: number) => child.name === String(index)
      )

      return isArray ? `[${children.length}]` : `{${children.length}}`
    },
    valueText(data: TreeNode): string {
      const value = data.value

      if (value === null || value === undefined) {
        return 'null'
      }

      if (typeof value === 'boolean') {
        return value ? 'true' : 'false'
      }

      if (typeof value === 'number') {
        return String(value)
      }

      return `"${String(value)}"`
    },
    valueClass(data: TreeNode): string {
      const value = data.value

      if (value === null || value === undefined) {
        return 'data-value-null'
      }

      if (typeof value === 'boolean') {
        return 'data-value-boolean'
      }

      if (typeof value === 'number') {
        return 'data-value-number'
      }

      return 'data-value-string'
    },
    valueTitle(data: TreeNode): string | undefined {
      const text: string = this.valueText(data)

      return text.length > longValueLength ? String(data.value) : undefined
    },
    onCopyPath(data: TreeNode) {
      if (this.isParentData(data)) {
        return
      }

      copyToClipboard(this.dataKeys[data.key])
    },
    expandBranch(data: TreeNode, expanded: boolean) {
      // @ts-ignore el-tree exposes its nodes through the store
      const node = this.$refs.treeRef?.store?.getNode(data.key)

      if (!node) {
        return
      }

      this.setExpandedDeep(node, expanded)
    },
    setExpandedDeep(node: any, expanded: boolean) {
      node.expanded = expanded

      const children: Array<any> = node.childNodes ?? []

      children.forEach((child: any) => this.setExpandedDeep(child, expanded))
    },
    collectExpandedKeys(nodes: Array<TreeNode>, keys: Array<string>) {
      nodes.forEach((node: TreeNode) => {
        if (!node.children) {
          return
        }

        keys.push(node.key)

        if (node.children.length > maxExpandedChildren) {
          return
        }

        this.collectExpandedKeys(node.children, keys)
      })
    },
    countMatches(nodes: Array<TreeNode>, query: string): number {
      return nodes.reduce(
          (count: number, node: TreeNode) => {
            const matched: boolean = this.searchStore.inValues
                ? !node.children && this.valueText(node).toLowerCase().includes(query)
                : node.name.toLowerCase().includes(query)

            return count
                + (matched ? 1 : 0)
                + (node.children ? this.countMatches(node.children, query) : 0)
          },
          0
      )
    },
    onCustomFieldShow(data: TreeNode) {
      if (this.isParentData(data)) {
        return
      }

      const value = this.dataValues[data.key]

      const parsed = this.parseValue(String(value))

      // a value that is itself json is worth the viewer rather than one long line
      if (parsed !== null && typeof parsed === 'object') {
        this.jsonDialog.value = parsed
        this.jsonDialog.visible = true

        return
      }

      this.dataDialog.data = value
      this.dataDialog.visible = true
    },
    onCustomFieldCopy(data: TreeNode) {
      if (this.isParentData(data)) {
        return
      }

      const dataValue = this.dataValues[data.key];

      copyToClipboard(dataValue)
    },
    onCustomFieldFilter(data: TreeNode) {
      if (this.isParentData(data)) {
        return
      }

      const dataValue = this.dataValues[data.key];

      const parameters: TraceAggregatorCustomFieldParameter = {
        field: this.dataKeys[data.key],
        value: dataValue,
        canBeFiltered: data.canBeFiltered
      }

      this.$emit('onCustomFieldClick', parameters)
    },
    buildJson(data: TraceAggregatorDetailData): any {
      if (!data.children || !data.children.length) {
        return this.parseValue(data.value)
      }

      const keys: Array<string> = data.children.map(
          (child: TraceAggregatorDetailData) => this.nodeEndKey(child.key)
      )

      // sequential numeric keys starting from zero => treat as an array
      const isArray: boolean = keys.every((key: string, index: number) => key === String(index))

      if (isArray) {
        return data.children.map(
            (child: TraceAggregatorDetailData) => this.buildJson(child)
        )
      }

      const result: Record<string, any> = {}

      data.children.forEach((child: TraceAggregatorDetailData) => {
        result[this.nodeEndKey(child.key)] = this.buildJson(child)
      })

      return result
    },
    parseValue(value: string): any {
      try {
        return JSON.parse(value)
      } catch (e) {
        return value
      }
    },
    onShowNodeJson(data: TreeNode) {
      const original: TraceAggregatorDetailData = this.dataNodes[data.key]

      if (!original) {
        return
      }

      const keyName: string = original.key ? this.nodeEndKey(original.key) : 'root'

      this.jsonDialog.value = {[keyName]: this.buildJson(original)}
      this.jsonDialog.visible = true
    },
    keyMatches(data: TreeNode, query: string): boolean {
      return typeof data?.key === 'string'
          && this.nodeEndKey(data.key).toLowerCase().includes(query)
    },
    filterNode(value: string, data: TreeNode, node: any): boolean {
      if (!value) {
        return true
      }

      const query = value.toLowerCase()

      if (this.searchStore.inValues) {
        const nodeValue = this.dataValues[data.key]

        return nodeValue !== undefined
            && nodeValue !== null
            && String(nodeValue).toLowerCase().includes(query)
      }

      // keys mode: show a node if its own key or any ancestor key matches,
      // so all descendants of a matched branch stay visible
      let current = node

      while (current) {
        if (current.data && this.keyMatches(current.data, query)) {
          return true
        }

        current = current.parent
      }

      return false
    },
    applyFilter() {
      // @ts-ignore el-tree exposes filter() via ref
      this.$refs.treeRef?.filter(this.searchStore.query)
    },
  },
  watch: {
    'searchStore.query'() {
      this.applyFilter()
    },
    'searchStore.inValues'() {
      this.applyFilter()
    },
    tree() {
      this.$nextTick(() => this.applyFilter())
    },
  },
  mounted() {
    if (this.searchStore.query) {
      this.applyFilter()
    }
  },
})
</script>

<style scoped>
.data-tree {
  --data-row-gap: 6px;
}

.data-tree :deep(.el-tree-node__content) {
  height: 24px;
}

.data-row {
  display: flex;
  align-items: center;
  gap: var(--data-row-gap);
  width: 100%;
  min-width: 0;
}

.data-branch {
  flex: none;
  color: var(--el-color-primary);
}

.data-key {
  flex: none;
  color: var(--el-color-primary);
}

.data-punct {
  flex: none;
  color: var(--el-text-color-regular);
}

.data-value {
  min-width: 0;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.data-value-string {
  color: var(--el-color-success);
}

.data-value-number {
  color: var(--el-color-warning);
}

.data-value-boolean {
  color: var(--el-color-danger);
}

.data-value-null {
  color: var(--el-text-color-placeholder);
  font-style: italic;
}

.data-hint {
  flex: none;
  color: var(--el-text-color-secondary);
}

.data-actions {
  flex: none;
  padding-left: 4px;
  display: inline-flex;
}

.data-actions :deep(.el-button) {
  height: 20px;
}


.data-toolbar {
  position: sticky;
  top: 0;
  z-index: 2;
  padding-left: 10px;
  padding-bottom: 8px;
  background-color: var(--el-bg-color);
}

.data-search-switch {
  --el-switch-on-color: var(--el-border-color);
  --el-switch-off-color: var(--el-border-color);
}
</style>
