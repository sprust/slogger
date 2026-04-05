<template>
  <el-dialog
      :model-value="pendingRequestStore.visible"
      title="Trace dynamic index in process."
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :show-close="false"
      :before-close="preventClose"
      style="width: 70%"
      align-center
  >
    <DynamicIndexCollections
        v-if="pendingRequestStore.data"
        :index="pendingRequestStore.data"
    />
    <el-text v-else>
      Waiting for dynamic index collections...
    </el-text>
    <template #footer>
      <el-button
          type="danger"
          @click="pendingRequestStore.requestCancel()"
      >
        Cancel
      </el-button>
    </template>
  </el-dialog>
</template>

<script lang="ts">
import {defineAsyncComponent, defineComponent} from "vue";
import {usePendingRequestStore} from "../store/pendingRequestStore.ts";

const DynamicIndexCollections = defineAsyncComponent(
    () => import("./pages/trace-aggregator/components/dynamic-indexes/DynamicIndexCollections.vue")
)

export default defineComponent({
  components: {DynamicIndexCollections},
  methods: {
    preventClose() {
      return false
    },
  },
  computed: {
    pendingRequestStore() {
      return usePendingRequestStore()
    },
  },
})
</script>
