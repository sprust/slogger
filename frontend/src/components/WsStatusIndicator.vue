<template>
  <div class="ws-status">
    <el-tooltip :content="title" placement="bottom" :show-after="200">
      <span class="ws-dot" :class="`ws-dot--${status}`"/>
    </el-tooltip>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {EchoContainer, type WsStatus} from "../utils/echoContainer.ts";

const status = EchoContainer.status()

const titles: Record<WsStatus, string> = {
  off: 'Live updates are not configured — the panel asks on a timer',
  idle: 'Live updates: not connected',
  connecting: 'Live updates: connecting',
  connected: 'Live updates: connected',
  lost: 'Live updates are unreachable — the panel asks on a timer',
}

export default defineComponent({
  computed: {
    // Read through a computed rather than held in data(): the ref belongs to the
    // container, and nothing here is allowed to write to it.
    status(): WsStatus {
      return status.value
    },
    title(): string {
      return titles[this.status]
    },
  },
})
</script>

<style scoped>
/*
 * The geometry of the menu items beside it: the full height of the bar, the same
 * transparent bottom border they reserve for the active underline, contents centred both
 * ways. The root of this component is what el-menu lays out, so the centring happens in
 * the one box that is certain to be a flex child of the bar — a wrapper in the header
 * would leave the tooltip's own node in between.
 *
 * `align-self`, not `height: 100%`: it holds whatever the bar sets on its children.
 *
 * Not an el-menu-item: there is nothing to click here, and one would bring a cursor, a
 * hover and a focus ring with it. Narrower than an item, too — an indicator standing in a
 * row of buttons should not take a button's width.
 */
.ws-status {
  display: flex;
  align-items: center;
  justify-content: center;
  align-self: stretch;
  box-sizing: border-box;
  padding: 0 12px;
  border-bottom: 2px solid transparent;
}

.ws-dot {
  flex: none;
  width: 13px;
  height: 13px;
  border-radius: 50%;
  background-color: var(--el-color-info-light-5);
}

.ws-dot--connected {
  background-color: var(--el-color-success);
}

.ws-dot--connecting {
  background-color: var(--el-color-warning);
  animation: ws-dot-pulse 1.2s ease-in-out infinite;
}

.ws-dot--lost {
  background-color: var(--el-color-danger);
}

@keyframes ws-dot-pulse {
  50% {
    opacity: 0.25;
  }
}

/* A dot that blinks for ever is worse than one that does not blink at all. */
@media (prefers-reduced-motion: reduce) {
  .ws-dot--connecting {
    animation: none;
  }
}
</style>
