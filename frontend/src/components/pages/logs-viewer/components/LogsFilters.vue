<template>
  <div class="logs-filters">
    <el-space wrap>
      <el-date-picker
          v-model="store.from"
          type="datetime"
          placeholder="From"
          format="YYYY-MM-DD HH:mm:ss"
          value-format="YYYY-MM-DD HH:mm:ss"
          style="width: 200px"
          :show-now="false"
          @change="$emit('search')"
      />
      <el-date-picker
          v-model="store.to"
          type="datetime"
          placeholder="To"
          format="YYYY-MM-DD HH:mm:ss"
          value-format="YYYY-MM-DD HH:mm:ss"
          style="width: 200px"
          :show-now="false"
          @change="$emit('search')"
      />
      <el-input
          v-model="store.searchQuery"
          placeholder="Search text"
          style="width: 300px"
          maxlength="255"
          clearable
          @keyup.enter="$emit('search')"
          @clear="$emit('search')"
      />
      <el-button
          :icon="IconSearch"
          :loading="store.loading"
          @click="$emit('search')"
      />
      <el-text type="info" class="logs-filters-status">
        {{ statusText }}
      </el-text>
      <el-button
          :class="{'logs-filters-hidden': !store.canContinueSearch}"
          :loading="store.continuing"
          @click="$emit('continue')"
      >
        Continue search
      </el-button>
    </el-space>

    <div v-for="group in levelGroups" :key="group.type" class="logs-filters-levels">
      <el-text class="logs-filters-levels-title">{{ group.title }}</el-text>
      <el-check-tag
          v-for="level in group.levels"
          :key="level.key"
          :checked="store.levels.includes(level.key)"
          :type="levelTagType(level.key)"
          class="logs-filters-level"
          @change="toggleLevel(level.key)"
      >
        {{ levelKeyName(level.key) }} ({{ level.count }})
      </el-check-tag>
    </div>
  </div>
</template>

<script lang="ts">
import {defineComponent} from 'vue'
import {Search as IconSearch} from '@element-plus/icons-vue'
import {useLogsViewerStore} from "../store/logsViewerStore.ts";
import {compareLevelKeys, levelKeyName, levelKeyType, levelTagType, logTypeTitle} from "../store/logLevels.ts";

type LevelCount = {
  key: string,
  count: number,
}

type LevelGroup = {
  type: string,
  title: string,
  levels: Array<LevelCount>,
}

export default defineComponent({
  emits: ['search', 'continue'],

  computed: {
    store() {
      return useLogsViewerStore()
    },
    IconSearch() {
      return IconSearch
    },
    // Levels of the chosen files; a chosen level stays even when its files are gone,
    // so it can be turned off.
    levelGroups(): Array<LevelGroup> {
      const counts = new Map<string, number>()

      for (const levelCount of this.store.page?.level_counts ?? []) {
        counts.set(levelCount.key, levelCount.count)
      }

      for (const key of this.store.levels) {
        if (!counts.has(key)) {
          counts.set(key, 0)
        }
      }

      const groups: Array<LevelGroup> = []

      for (const key of [...counts.keys()].sort()) {
        const type = levelKeyType(key)

        let group = groups.find((item) => item.type === type)

        if (!group) {
          group = {type: type, title: logTypeTitle(type), levels: []}

          groups.push(group)
        }

        group.levels.push({key: key, count: counts.get(key) ?? 0})
      }

      for (const group of groups) {
        group.levels.sort((a, b) => compareLevelKeys(a.key, b.key))
      }

      return groups
    },
    statusText(): string {
      const page = this.store.page

      if (!page) {
        return ''
      }

      if (page.indexing) {
        const indexedMb = (page.indexed_bytes / 1024 / 1024).toFixed(1)
        const totalMb = (page.total_bytes / 1024 / 1024).toFixed(1)

        return `Indexing: ${indexedMb} of ${totalMb} MB`
      }

      if (this.store.isSearch) {
        return `Checked ${this.store.scanned} of ${page.total}`
      }

      return `Entries: ${page.total}`
    },
  },

  methods: {
    levelKeyName,
    levelTagType,
    toggleLevel(key: string) {
      this.store.levels = this.store.levels.includes(key)
          ? this.store.levels.filter((item) => item !== key)
          : [...this.store.levels, key]

      this.$emit('search')
    },
  },
})
</script>

<style scoped>
.logs-filters {
  margin-bottom: 10px;
}

.logs-filters-status {
  display: inline-block;
  min-width: 220px;
}

.logs-filters-hidden {
  visibility: hidden;
}

.logs-filters-levels {
  margin-top: 5px;
}

.logs-filters-levels-title {
  display: inline-block;
  min-width: 110px;
}

.logs-filters-level {
  margin-right: 5px;
}
</style>
