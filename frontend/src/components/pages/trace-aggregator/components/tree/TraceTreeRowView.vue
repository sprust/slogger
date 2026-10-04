<script lang="ts">

import {defineComponent, PropType} from "vue";
import type {TraceTreeNode} from "./store/traceAggregatorTreeStore.ts";
import {CaretBottom, CaretRight} from "@element-plus/icons-vue";

export default defineComponent({
  components: {CaretBottom, CaretRight},

  props: {
    row: {
      type: Object as PropType<TraceTreeNode>,
      required: true
    },
    serviceName: {
      type: String,
      required: true
    },
    showPid: {
      type: Boolean,
      default: false
    },
    pidChanged: {
      type: Boolean,
      default: false
    },
    selected: {
      type: Boolean,
      default: false
    },
    highlighted: {
      type: Boolean,
      default: false
    },
    indicatorWidthPercent: {
      type: Number,
      default: 0
    },
    serviceSelected: {
      type: Boolean,
      default: false
    },
    typeSelected: {
      type: Boolean,
      default: false
    },
    statusSelected: {
      type: Boolean,
      default: false
    },
    selectedTags: {
      type: Array as PropType<Array<string>>,
      default: () => []
    },
  },

  emits: ['select', 'toggle-collapse', 'find-tree', 'show-json', 'indicate', 'load-more'],

  methods: {
    makeTreeNodeStyle() {
      const style: { 'background-color'?: string, 'border'?: string } = {}

      if (this.selected) {
        style['background-color'] = 'red'
      }

      if (this.highlighted) {
        style['border'] = '1px solid green'
      }

      return style
    },
    makeTraceIndicatorStyle() {
      return {
        width: this.indicatorWidthPercent + 'vw',
      }
    },
    isTagSelected(item: string): boolean {
      return this.selectedTags.indexOf(item) != -1
    },
    hasChildren(): boolean {
      return this.row.children.length > 0 || (this.row.childrenCount ?? 0) > 0
    },
    loadMoreLabel(): string {
      const parent = this.row.loadMoreOf!

      return `more (${parent.children.length} of ${parent.childrenCount ?? '?'} loaded)`
    },
    getIndicatorBackground() {
      if (!this.row.indicatorPercent) {
        return ''
      }

      const indicatorPercent = Math.round(this.row.indicatorPercent / 2)

      return `linear-gradient(to left, #ff000030 ${indicatorPercent}%, transparent ${indicatorPercent}%)`
    }
  },
})
</script>

<template>
  <el-row v-if="row.loadMoreOf" :style="{height: '30px', width: '100%'}">
    <el-row :style="{width: '100%', 'padding-left': row.depth * 20 + 'px'}">
      <span class="collapse-placeholder"/>
      <el-button
          type="primary"
          link
          :loading="row.loadMoreOf.childrenLoading"
          @click="$emit('load-more')"
      >
        {{ loadMoreLabel() }}
      </el-button>
    </el-row>
  </el-row>
  <el-row v-else :style="{height: '30px', width: '100%', background: getIndicatorBackground()}">
    <el-row :style="{width: '100%', 'padding-left': row.depth * 20 + 'px'}">
      <el-space>
        <el-button
            v-if="hasChildren()"
            type="info"
            size="small"
            :loading="row.childrenLoading"
            @click.stop="$emit('toggle-collapse')"
            link
            class="collapse-toggle"
        >
          <el-icon v-if="!row.childrenLoading">
            <CaretRight v-if="row.collapsed"/>
            <CaretBottom v-else/>
          </el-icon>
        </el-button>
        <span v-else class="collapse-placeholder"/>
        <div class="trace-tree-metric-indicator" :style="makeTraceIndicatorStyle()"/>
        <div class="trace-tree-select-indicator" :style="makeTreeNodeStyle()"/>
      </el-space>

      <el-space spacer=":" @click="$emit('select')" style="cursor: pointer">
        <div>
          <el-text :type="serviceSelected ? 'danger': 'primary'">
            {{ serviceName }}
          </el-text>
        </div>
        <div v-if="showPid">
          <el-text :type="pidChanged ? 'danger' : 'info'">
            {{ row.primary.pid ?? 'null' }}
          </el-text>
        </div>
        <div>
          <el-text :type="typeSelected ? 'danger': 'success'">
            {{ row.primary.type }}
          </el-text>
        </div>
        <el-space v-if="row.primary.tags.length" spacer="/">
          <el-text
              v-for="tag in row.primary.tags"
              :type="isTagSelected(tag) ? 'danger': 'warning'"
              style="padding-right: 3px"
          >
            {{ tag.slice(0, 100) }}
          </el-text>
        </el-space>
      </el-space>
      <el-button
          type="info"
          @click="$emit('find-tree')"
          link
      >
        tree
      </el-button>
      <el-button
          type="info"
          @click="$emit('show-json')"
          link
      >
        json
      </el-button>
      <el-button
          v-if="row.children.length > 0"
          type="info"
          @click="$emit('indicate')"
          link
      >
        indicate
      </el-button>
      <el-text v-if="row.childrenCount !== undefined && row.childrenCount > 0" type="info">
        ({{ row.childrenCount }})
      </el-text>

      <div class="flex-grow"/>

      <el-space spacer="|">
          <el-text :type="statusSelected ? 'danger': ''">
            {{ row.primary.status }}
          </el-text>
          <el-text>
            {{ row.primary.logged_at }}
          </el-text>
          <el-text>
            {{ row.primary.memory }}
          </el-text>
          <el-text>
            {{ row.primary.cpu }}
          </el-text>
          <el-text>
            {{ row.primary.duration }}
          </el-text>
      </el-space>
    </el-row>
  </el-row>
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

.collapse-toggle {
  width: 20px;
  padding: 0;
}

.collapse-placeholder {
  width: 20px;
  display: inline-block;
}
</style>
