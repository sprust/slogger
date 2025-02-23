<template>
  <el-tree
      :data="tree"
      :props="treeProps"
      node-key="key"
      :expand-on-click-node="false"
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
</template>

<script lang="ts">
import {defineComponent, PropType} from "vue";
import {
  TraceAggregatorCustomFieldParameter,
} from "../store/traceAggregatorStore.ts";
import {ElTree} from 'element-plus'
import {TraceAggregatorDetailData} from "../store/traceAggregatorDataStore.ts";
import {copyToClipboard} from "../../../../utils/helpers.ts";
import FilterTagsSection from "../widgets/FilterTagsSection.vue";

type TreeNode = {
  key: string,
  label: string,
  children: null | Array<TreeNode>,
  disabled: boolean
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
  },
  data() {
    return {
      dataValues: {} as any,
      dataKeys: {} as any,
      dataDialog: {
        visible: false,
        data: null,
      },
      treeProps: {
        children: 'children',
        label: 'label',
        disabled: 'disabled',
      },
    }
  },
  computed: {
    tree(): Array<TreeNode> {
      return [this.dataNodeToTree(this.data, 0, 'root')]
    },
  },
  methods: {
    nodeEndKey(key: string) {
      const keyData: Array<string> = key.split(".");

      return keyData[keyData.length - 1]
    },
    isParentData(data: TraceAggregatorDetailData): boolean {
      return !!data.children
    },
    dataNodeToTree(data: TraceAggregatorDetailData, index: number, postNodeKey: string): TreeNode {
      const nodeKey: string = `${postNodeKey}.${index}.${this.nodeEndKey(data.key)}`

      if (!data.children) {
        this.dataValues[nodeKey] = data.value
        this.dataKeys[nodeKey] = data.key

        return {
          key: nodeKey,
          label: this.nodeEndKey(nodeKey) + ' ==> ' + data.value,
          children: null,
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
        disabled: true,
      }
    },
    onCustomFieldShow(data: TraceAggregatorDetailData) {
      if (this.isParentData(data)) {
        return
      }

      this.dataDialog.data = this.dataValues[data.key]
      this.dataDialog.visible = true
    },
    onCustomFieldCopy(data: TraceAggregatorDetailData) {
      if (this.isParentData(data)) {
        return
      }

      const dataValue = this.dataValues[data.key];

      copyToClipboard(dataValue)
    },
    onCustomFieldFilter(data: TraceAggregatorDetailData) {
      if (this.isParentData(data)) {
        return
      }

      const dataValue = this.dataValues[data.key];

      const parameters: TraceAggregatorCustomFieldParameter = {
        field: this.dataKeys[data.key],
        value: dataValue,
      }

      this.$emit('onCustomFieldClick', parameters)
    }
  },
})
</script>

<style scoped>
.parent-tree-node {
  color: blue;
}
</style>
