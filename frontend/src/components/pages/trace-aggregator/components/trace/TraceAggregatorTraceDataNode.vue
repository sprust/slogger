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
    </el-space>
  </el-row>

  <el-tree
      ref="treeRef"
      :data="tree"
      :props="treeProps"
      node-key="key"
      :expand-on-click-node="false"
      :filter-node-method="filterNode"
      default-expand-all
  >
    <template #default="{ node, data }">
      <el-row style="width: 100%">
        <el-text
            :class="isParentData(data) ? 'parent-tree-node' : ''"
            style="max-width: 70%; padding-right: 5px"
            truncated
        >
          {{ node.label }}
        </el-text>
        <el-space v-if="isParentData(data)">
          <el-button type="info" @click="onShowNodeJson(data)" link>
            json
          </el-button>
        </el-space>
        <el-space v-if="!isParentData(data)" spacer="|">
          <el-button type="info" @click="onCustomFieldShow(data)" link>
            show
          </el-button>
          <el-button type="info" @click="onCustomFieldCopy(data)" link>
            copy
          </el-button>
          <el-button v-if="showCustomButton" type="info" @click="onCustomFieldFilter(data)" link>
            custom
          </el-button>
        </el-space>
      </el-row>
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
    <template #header>
      <el-button type="info" @click="onCopyJson" link>
        copy
      </el-button>
    </template>
    <el-row style="min-height: 80vh; overflow: auto">
      <pre>{{ jsonDialog.data }}</pre>
    </el-row>
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
import FilterTagsSection from "../tags/FilterTagsSection.vue";

type TreeNode = {
  key: string,
  label: string,
  children: null | Array<TreeNode>,
  canBeFiltered: boolean,
  disabled: boolean,
}

export default defineComponent({
  components: {FilterTagsSection},
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
        data: '',
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

        return {
          key: nodeKey,
          label: this.nodeEndKey(nodeKey) + ' ==> ' + data.value,
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

      return {
        key: nodeKey,
        label: data.key ? this.nodeEndKey(data.key) : 'root',
        children: children,
        canBeFiltered: data.can_be_filtered,
        disabled: true,
      }
    },
    onCustomFieldShow(data: TreeNode) {
      if (this.isParentData(data)) {
        return
      }

      this.dataDialog.data = this.dataValues[data.key]
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

      this.jsonDialog.data = JSON.stringify({[keyName]: this.buildJson(original)}, null, 2)
      this.jsonDialog.visible = true
    },
    onCopyJson() {
      copyToClipboard(this.jsonDialog.data)
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
.parent-tree-node {
  color: blue;
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
