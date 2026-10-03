<template>
  <LandingSection anchor="watchers" :title="text.title" :paragraphs="text.paragraphs">
    <p class="landing-caption">
      <el-text type="info">{{ text.ruleCaption }}</el-text>
    </p>
    <WatcherListView
        :items="watchers"
        :type-titles="typeTitles"
        :channel-names="channelNames"
        :show-actions="false"
    />
    <el-row :gutter="16" class="landing-watchers">
      <el-col :span="16">
        <p class="landing-caption">
          <el-text type="info">{{ text.eventsCaption }}</el-text>
        </p>
        <el-card shadow="never" body-class="landing-events-body">
          <el-scrollbar class="landing-events">
            <IncidentEventsTable
                :events="events"
                :columns="columns"
                :service-names="serviceNames"
                :exhausted="true"
                :can-open-in-aggregator="false"
                :expanded-event-ids="expandedEventIds"
                @expand-change="expandedEventIds = $event"
            />
          </el-scrollbar>
        </el-card>
      </el-col>
      <el-col :span="8">
        <p class="landing-caption">
          <el-text type="info">{{ text.channelsCaption }}</el-text>
        </p>
        <el-card v-for="channel in text.channels" :key="channel.name" shadow="never" class="landing-channel">
          <template #header>
            <el-text tag="b">{{ channel.name }}</el-text>
          </template>
          <el-text>{{ channel.text }}</el-text>
        </el-card>
        <p>
          <el-text type="info">{{ text.channelsNote }}</el-text>
        </p>
      </el-col>
    </el-row>
  </LandingSection>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import LandingSection from "./LandingSection.vue";
import IncidentEventsTable from "../../watchers/components/incidents/IncidentEventsTable.vue";
import WatcherListView from "../../watchers/components/settings/WatcherListView.vue";
import type {Watcher} from "../../watchers/store/watchersStore.ts";
import type {PayloadColumn} from "../../watchers/components/incidents/incidentEventColumns.ts";
import type {WatcherIncidentEvent} from "../../watchers/store/incidentsStore.ts";
import type {LandingWatchersText} from "../content/types.ts";
import {landingText} from "../content/locale.ts";
import {
  demoChannelId,
  demoIncidentColumns,
  demoIncidentEvents,
  demoWatchers,
  demoWatcherTypeTitles
} from "../demo/demoIncident.ts";
import {demoServiceNames} from "../demo/demoTraceTree.ts";

export default defineComponent({
  components: {LandingSection, IncidentEventsTable, WatcherListView},

  data() {
    return {
      expandedEventIds: ['demo-event-2'] as Array<string>,
    }
  },

  computed: {
    text(): LandingWatchersText {
      return landingText().watchers
    },
    events(): Array<WatcherIncidentEvent> {
      return demoIncidentEvents
    },
    columns(): Array<PayloadColumn> {
      return demoIncidentColumns
    },
    serviceNames(): Record<number, string> {
      return demoServiceNames
    },
    watchers(): Array<Watcher> {
      return demoWatchers.map((watcher: Watcher) => ({...watcher, name: this.text.demoWatcherName}))
    },
    typeTitles(): Record<string, string> {
      return demoWatcherTypeTitles
    },
    channelNames(): Record<number, string> {
      return {[demoChannelId]: this.text.demoChannelName}
    },
  },
})
</script>

<style scoped>
.landing-caption {
  margin: 16px 0 8px 0;
}

:deep(.landing-events-body) {
  padding: 10px 0;
}

.landing-events {
  height: 360px;
}

.landing-channel {
  margin-bottom: 12px;
}
</style>
