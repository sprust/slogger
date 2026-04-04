<script lang="ts">
import {defineComponent, PropType} from "vue";
import {
  TraceDynamicIndex,
  TraceDynamicIndexInfo,
  useTraceDynamicIndexesStore
} from "./store/traceDynamicIndexesStore.ts";

export default defineComponent({
  props: {
    index: {
      type: Object as PropType<TraceDynamicIndex>,
      required: true,
    },
  },

  computed: {
    traceDynamicIndexesStore() {
      return useTraceDynamicIndexesStore()
    },
  },

  methods: {
    isCollectionInProcess(indexName: string, collectionName: string): boolean {
      const indexes = this.traceDynamicIndexesStore.traceDynamicIndexStats.indexes_in_process.filter(
          (item: TraceDynamicIndexInfo) => item.name === indexName
      )

      if (!indexes.length) {
        return false
      }

      return indexes.map((item: TraceDynamicIndexInfo) => item.collectionName).flat().includes(collectionName)
    }
  },
})
</script>

<template>
  <el-row class="collections-row">
    <el-tag
        v-for="collectionName in index.collectionNames"
        :type="isCollectionInProcess(index.indexName, collectionName) ? 'warning' : 'info'"
        style="margin: 2px"
    >
      {{ collectionName }}
    </el-tag>
  </el-row>
</template>

<style scoped>
.collections-row {
  max-height: 200px;
  overflow-y: auto;
}
</style>
