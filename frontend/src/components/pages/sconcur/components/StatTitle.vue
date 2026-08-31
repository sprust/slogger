<script lang="ts">
import {defineComponent} from "vue";

/**
 * How long the cursor has to rest on a name before its explanation appears.
 *
 * Long enough that crossing the table does not raise a tooltip over the next row on the
 * way past, short enough to arrive while you are still looking at the header you stopped
 * on.
 */
const SHOW_AFTER_MS = 500;

/**
 * A name on the Sconcur page — a summary card's title or a table column's header — with
 * what it means behind a hover.
 *
 * The page counts things the panel names in its own terms (a hung worker is not a stuck
 * request; Finished counts a refused delivery), and none of that fits in a column header
 * two words wide. It lives here instead, one component so the same column explains itself
 * the same way in the Groups table, the Workers table and the summary above them.
 */
export default defineComponent({
  props: {
    label: {type: String, required: true},
    tip: {type: String, required: true},
    /** A quieter second line under the name — the Avg column says what its two numbers are. */
    legend: {type: String, default: ''},
  },

  computed: {
    showAfter(): number {
      return SHOW_AFTER_MS
    },
  },
})
</script>

<template>
  <el-tooltip :content="tip" placement="top" :show-after="showAfter" popper-class="stat-title-tip">
    <span class="stat-title">
      <span>{{ label }}</span>
      <span v-if="legend" class="stat-title-legend">{{ legend }}</span>
    </span>
  </el-tooltip>
</template>

<style scoped>
.stat-title {
  line-height: 1.2;
  /* The trigger is this block, and a header cell is wider than the name in it — stretched
     to the cell, the tooltip would come up over the empty rest of it rather than over the
     word it explains. */
  display: inline-block;
}

.stat-title-legend {
  display: block;
  color: var(--el-text-color-secondary);
  font-weight: normal;
}
</style>

<style>
/* The popper is teleported out of this component, so scoped styles never reach it. Left
   alone it takes one line however long the sentence is; a few of these are two sentences. */
.stat-title-tip {
  max-width: 340px;
  line-height: 1.45;
}
</style>
