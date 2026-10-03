<template>
  <LandingSection anchor="stack" :title="text.title">
    <h3>{{ text.runtimeCaption }}</h3>
    <p v-for="paragraph in text.runtime" class="landing-paragraph">
      <el-text>{{ paragraph }}</el-text>
    </p>
    <h3>{{ text.storageCaption }}</h3>
    <el-table :data="text.storages" border>
      <el-table-column prop="name" :label="text.storageNameColumn" width="180"/>
      <el-table-column prop="role" :label="text.storageRoleColumn"/>
    </el-table>
    <h3>{{ text.cleanupCaption }}</h3>
    <p v-for="paragraph in text.cleanup" class="landing-paragraph">
      <el-text>{{ paragraph }}</el-text>
    </p>
  </LandingSection>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import LandingSection from "./LandingSection.vue";
import {LandingStackText, landingText} from "../content/ru.ts";

export default defineComponent({
  components: {LandingSection},

  computed: {
    text(): LandingStackText {
      return landingText.stack
    },
  },
})
</script>

<style scoped>
.landing-paragraph {
  line-height: 1.6;
}
</style>
