<template>
  <div style="width: 100%">
    <el-row>
      <el-space>
        <div>
          Services
        </div>
        <el-button
            :loading="store.state.loading"
            :icon="store.state.loading ? Loading : Refresh"
            link
            @click="update"
        />
      </el-space>
    </el-row>
    <el-divider></el-divider>
    <el-table :data="store.state.services" :border="true">
      <el-table-column type="expand">
        <template #default="props">
          <el-table :data="[props.row]" :border="true">
            <el-table-column label="Service">
              <template #default="props">
                {{ props.row.name }}
              </template>
            </el-table-column>
            <template v-for="period in store.state.periods" :key="period">
              <el-table-column :prop="period" :label="period">
                <template #default="props">
                  <div v-for="type in getTypes(props.row, period)" :key="type.name">
                    {{ type.name }}:
                    <div v-for="status in type.statuses" :key="status.name">
                      {{ '- ' + status.name + ': ' + status.count }}
                    </div>
                  </div>
                </template>
              </el-table-column>
            </template>
          </el-table>
        </template>
      </el-table-column>

      <el-table-column label="Service">
        <template #default="props">
          {{ props.row.name }}
        </template>
      </el-table-column>

      <template v-for="period in store.state.periods" :key="period">
        <el-table-column :prop="period" :label="period">
          <template #default="props">
            {{ getTracesCountByPeriod(props.row, period) }}
          </template>
        </el-table-column>
      </template>
    </el-table>
  </div>
</template>

<script lang="ts">
import {defineComponent} from "vue";
import {Loading, Refresh} from '@element-plus/icons-vue'
import {Service, Type, useDashboardServiceStatStore} from "../../../../store/dashboardServiceStatStore.ts";

export default defineComponent({
  data() {
    return {
      store: useDashboardServiceStatStore(),
    }
  },
  computed: {
    Loading() {
      return Loading
    },
    Refresh() {
      return Refresh
    },
  },
  methods: {
    update() {
      this.store.dispatch('findDashboardServiceStat')
    },
    getTracesCountByPeriod(service: Service, period: string): number {
      let count = 0

      service.periods[period]?.types.forEach(type => {
        type.statuses.map(status => {
          count += status.count
        })
      })

      return count
    },
    getTypes(service: Service, period: string): Array<Type> {
      return service.periods[period]?.types ?? []
    },
  },
  mounted() {
    if (!this.store.state.loading) {
      return
    }

    this.update()
  },
})

</script>

<style scoped>

</style>
