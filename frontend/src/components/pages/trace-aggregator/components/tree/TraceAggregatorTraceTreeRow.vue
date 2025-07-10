<script lang="ts">

import {defineComponent, PropType, ref} from "vue";
import TraceService from "../services/TraceService.vue";
import {TraceAggregatorTreeRow, useTraceAggregatorTreeStore} from "./store/traceAggregatorTreeStore.ts";
import {convertDateStringToLocal} from "../../../../../utils/helpers.ts";

export default defineComponent({
  components: {TraceService},

  props: {
    row: {
      type: Object as PropType<TraceAggregatorTreeRow>,
      required: true
    }
  },

  computed: {
    traceAggregatorTreeStore() {
      return useTraceAggregatorTreeStore()
    },
    isVisible() {
      return ref(false)
    },
    traceTreeRowRef() {
      return ref<HTMLElement | null>(null)
    },
    originalHeight() {
      return ref('30px')
    },
    observer() {
      return null as IntersectionObserver | null
    }
  },

  methods: {
    convertDateStringToLocal,
    makeTreeNodeStyle(trace: TraceAggregatorTreeRow) {
      const style: { 'background-color'?: string, 'border'?: string } = {}

      if (trace.trace_id === this.traceAggregatorTreeStore.selectedTrace.trace_id) {
        style['background-color'] = 'red'
      }

      if (trace.trace_id === this.traceAggregatorTreeStore.parameters.trace_id) {
        style['border'] = '1px solid green'
      }

      return style
    },
    makeTraceIndicatorStyle(trace: TraceAggregatorTreeRow) {
      let percent = 0

      if (trace.duration && this.traceAggregatorTreeStore.traceIndicatingIds.indexOf(trace.trace_id) !== -1) {
        percent = (trace.duration / this.traceAggregatorTreeStore.traceTotalIndicatorsNumber) * 50
      }

      return {
        width: percent + 'vw',
      }
    },
    onClickOnRow(treeNode: TraceAggregatorTreeRow) {
      this.traceAggregatorTreeStore.findData(treeNode.trace_id)
    },
    getServiceName(treeNode: TraceAggregatorTreeRow) {
      return this.traceAggregatorTreeStore.servicesMap[treeNode.service_id]?.name ?? 'NO LOAD'
    },
    isServiceIdSelected(serviceId: number): boolean {
      return this.traceAggregatorTreeStore.selectedTraceServiceIds.indexOf(serviceId) != -1
    },
    isTypeSelected(item: string): boolean {
      return this.traceAggregatorTreeStore.selectedTraceTypes.indexOf(item) != -1
    },
    isTagSelected(item: string): boolean {
      return this.traceAggregatorTreeStore.selectedTraceTags.indexOf(item) != -1
    },
    isStatusSelected(item: string): boolean {
      return this.traceAggregatorTreeStore.selectedTraceStatuses.indexOf(item) != -1
    },
  },

  mounted() {
    if (this.traceTreeRowRef.value) {
      this.originalHeight.value = `${this.traceTreeRowRef.value.offsetHeight}px`

      this.observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              this.isVisible.value = entry.isIntersecting
            })
          },
          {
            threshold: 0.1,
            root: document.querySelector('.virtual-list'),
            rootMargin: '100px 0px'
          }
      )

      this.observer.observe(this.traceTreeRowRef.value)
    }
  },

  beforeUnmount() {
    if (this.observer && this.traceTreeRowRef.value) {
      this.observer.unobserve(this.traceTreeRowRef.value)
      this.observer.disconnect()
    }
  },
})
</script>

<template>
  <div ref="traceTreeRowRef" :style="{ height: originalHeight, width: '100%' }">
    <el-row v-if="isVisible" style="width: 100%">
      <el-space>
        <div class="trace-tree-metric-indicator" :style="makeTraceIndicatorStyle(row)"/>
        <div class="trace-tree-select-indicator" :style="makeTreeNodeStyle(row)"/>
      </el-space>

      <el-space spacer=":" @click="onClickOnRow(row)" style="cursor: pointer">
        <div>
          <el-text :type="isServiceIdSelected(row.service_id) ? 'danger': 'primary'">
            {{ getServiceName(row) }}
          </el-text>
        </div>
        <div>
          <el-text :type="isTypeSelected(row.type) ? 'danger': 'success'">
            {{ row.type }}
          </el-text>
        </div>
        <el-space v-if="row.tags.length" spacer="+">
          <el-text
              v-for="tag in row.tags"
              :type="isTagSelected(tag) ? 'danger': 'warning'"
              style="padding-right: 3px"
          >
            {{ tag.slice(0, 100) }}
          </el-text>
        </el-space>
      </el-space>

      <div class="flex-grow"/>

      <el-space spacer="|">
        <div>
          <el-text :type="isStatusSelected(row.status) ? 'danger': ''">
            {{ row.status }}
          </el-text>
        </div>
        <div>
          <el-text>
            {{ convertDateStringToLocal(row.logged_at) }}
          </el-text>
        </div>
        <div>
          <el-text>
            {{ row.memory }}
          </el-text>
        </div>
        <div>
          <el-text>
            {{ row.cpu }}
          </el-text>
        </div>
        <div>
          <el-text>
            {{ row.duration }}
          </el-text>
        </div>
      </el-space>
    </el-row>
  </div>
</template>

<style scoped>
.trace-tree-select-indicator {
  margin-right: 3px;
  width: 10px;
  height: 10px;
  border-radius: 20px 20px 20px 20px;
}

.trace-tree-metric-indicator {
  position: absolute;
  display: flex;
  background-color: rgb(139, 0, 0, 30%);
  right: 0;
  height: 20px;
}

.flex-grow {
  flex-grow: 1;
}
</style>
