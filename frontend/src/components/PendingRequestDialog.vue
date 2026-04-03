<template>
  <el-dialog
      :model-value="pendingRequestStore.visible"
      title="Trace dynamic index in process."
      width="420"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :show-close="false"
      :before-close="preventClose"
      align-center
  >
    <el-progress
        status="success"
        :percentage="100"
        :indeterminate="true"
        :duration="5"
        striped
    />
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
import {defineComponent} from "vue";
import {usePendingRequestStore} from "../store/pendingRequestStore.ts";

export default defineComponent({
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
