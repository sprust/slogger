<template>
  <LandingSection anchor="search" :title="text.title" :paragraphs="text.paragraphs">
    <LandingTraceList/>
  </LandingSection>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import LandingSection from "./LandingSection.vue";
import LandingTraceList from "./LandingTraceList.vue";
import {LandingSearchText, landingText} from "../content/ru.ts";

export default defineComponent({
  components: {LandingSection, LandingTraceList},

  computed: {
    text(): LandingSearchText {
      return landingText.search
    },
  },
})
</script>
