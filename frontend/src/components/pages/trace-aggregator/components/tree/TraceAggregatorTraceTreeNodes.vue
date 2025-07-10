<template>
  <el-tree
      ref="treeRef"
      :data="tree"
      node-key="id"
      default-expand-all
      :expand-on-click-node="false"
      :props="treeProps"
      style="width: 100%"
      :filter-node-method="filterNode"
  >
    <template #default="{ data }">
      <el-row style="width: 100%">
        <TraceAggregatorTraceTreeRow :row="data.primary"/>
      </el-row>
    </template>
  </el-tree>
</template>

<script lang="ts">
import {TraceTreeNode, useTraceAggregatorTreeStore} from "./store/traceAggregatorTreeStore.ts";
import TraceAggregatorTraceTreeRow from "./TraceAggregatorTraceTreeRow.vue";
import {defineComponent, ref} from "vue";
import {TreeInstance} from "element-plus";

export default defineComponent({
  components: {
    TraceAggregatorTraceTreeRow,
  },

  props: {
    tree: {
      type: Array<TraceTreeNode>,
      required: true
    }
  },

  data() {
    return {
      treeProps: {
        key: 'id',
        children: 'children',
        primary: 'primary',
      },
    }
  },

  computed: {
    treeRef() {
      return ref<TreeInstance>()
    },
    traceAggregatorTreeStore() {
      return useTraceAggregatorTreeStore()
    },
  },

  methods: {
    filter() {
      this.$refs.treeRef!.filter(null)
    },
    filterNode(value: any, data: TraceTreeNode) {
      const store = this.traceAggregatorTreeStore
      const primary = data.primary

      if (store.selectedTraceServiceIds.length > 0
          && store.selectedTraceServiceIds.indexOf(primary.service_id) != -1
      ) {
        return false
      }

      if (store.selectedTraceTypes.length > 0
          && store.selectedTraceTypes.indexOf(primary.type) === -1
      ) {
        return false
      }

      if (store.selectedTraceTags.length > 0) {
        if (primary.tags.length === 0) {
          return false
        }

        const existTags = primary.tags.filter(
            (tag: string) => {
              return store.selectedTraceTags.indexOf(tag) != -1
            }
        )

        if (existTags.length === 0) {
          return false
        }
      }

      if (store.selectedTraceStatuses.length > 0
          && store.selectedTraceStatuses.indexOf(primary.status) === -1
      ) {
        return false
      }

      return true
    }
  },

  watch: {
    'filterText'(val) {
      this.treeRef.value!.filter(val)
    }
  }
})
</script>

<style scoped>

</style>
