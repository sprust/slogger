// components/VirtualList.vue
<template>
  <div
      class="virtual-list"
      ref="containerRef"
      @scroll="handleScroll"
      style="width: 100%"
  >
    <div
        class="virtual-list-phantom"
        :style="{ height: totalHeight + 'px' }"
    />
    <div
        class="virtual-list-content"
        :style="{ transform: `translateY(${offsetY}px)` }"
    >
      <el-row v-for="row in visibleItems" style="width: 100%; height: 30px" class="current-tree-row">
        <TraceAggregatorTraceTreeRow :row="row" :depth="row.depth"/>
      </el-row>
    </div>
  </div>
</template>

<script lang="ts">
import {TraceTreeNode} from "./store/traceAggregatorTreeStore.ts";
import TraceAggregatorTraceTreeRow from "./TraceAggregatorTraceTreeRow.vue";

export default {
  name: 'VirtualList',

  components: {
    TraceAggregatorTraceTreeRow,
  },

  props: {
    items: {
      type: Array<TraceTreeNode>,
      required: true
    },
    itemHeight: {
      type: Number,
      default: 30
    }
  },

  data() {
    return {
      containerHeight: 0,
      scrollTop: 0,
      visibleCount: 0
    };
  },
  computed: {
    totalHeight(): number {
      return this.items.length * this.itemHeight;
    },
    startIndex(): number {
      return Math.floor(this.scrollTop / this.itemHeight);
    },
    endIndex(): number {
      return Math.min(
          this.startIndex + this.visibleCount,
          this.items.length
      );
    },
    offsetY(): number {
      return this.startIndex * this.itemHeight;
    },
    visibleItems(): TraceTreeNode[] {
      return this.items.slice(this.startIndex, this.endIndex);
    }
  },
  mounted() {
    this.updateVisibleCount();
    window.addEventListener('resize', this.updateVisibleCount);
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.updateVisibleCount);
  },
  methods: {
    updateVisibleCount() {
      const container = this.$refs.containerRef as HTMLElement;
      this.containerHeight = container.clientHeight;
      this.visibleCount = Math.ceil(this.containerHeight / this.itemHeight) + 2;
    },
    handleScroll(event: Event) {
      const target = event.target as HTMLElement;
      this.scrollTop = target.scrollTop;
    }
  }
};
</script>

<style scoped>
.virtual-list {
  height: 100%;
  overflow-y: auto;
  position: relative;
}

.virtual-list-phantom {
  position: absolute;
  left: 0;
  top: 0;
  right: 0;
  z-index: -1;
  height: 100%;
}

.virtual-list-content {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  will-change: transform;
  height: 100%;
}

:deep(.current-tree-row) {
  &:hover {
    background-color: rgb(139, 139, 139, 0.1);
  }
}
</style>
