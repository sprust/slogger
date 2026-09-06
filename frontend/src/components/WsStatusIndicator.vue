<template>
  <el-tooltip :content="title" placement="bottom" :show-after="200">
    <span class="ws-dot" :class="`ws-dot--${status}`"/>
  </el-tooltip>
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
.ws-dot {
  display: block;
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
