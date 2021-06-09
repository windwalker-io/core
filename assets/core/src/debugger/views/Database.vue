<template>
  <default-layout>
    <template #title>
      Database
    </template>

    <div v-if="data" class="p-4">
      <!-- DB info -->

      <div>
        <h4>Queries</h4>

        <div class="my-3">
          Count: <span class="bg-blue-300 text-blue-600 px-2 py-1 rounded-sm text-sm">{{ data?.queries?.length }}</span>
          -
          Time: <span class="bg-blue-300 text-blue-600 px-2 py-1 rounded-sm text-sm">{{ round(totalTime) }}ms</span>
          -
          Memory: <span class="bg-blue-300 text-blue-600 px-2 py-1 rounded-sm text-sm">{{ round(totalMemory) }}MB</span>
        </div>
      </div>

      <div class="mt-5">
        <div class="mb-4" v-for="(query, i) of data.queries">
          <query-info :item="query" :i="i + 1" />
        </div>
      </div>

    </div>
  </default-layout>
</template>

<script>
import { computed, ref } from 'vue';
import QueryInfo from '../components/db/query-info.vue';
import DefaultLayout from '../layouts/DefaultLayout.vue';
import $http from '../services/http.js';

export default {
  name: 'Database',
  components: { DefaultLayout, QueryInfo },
  async beforeRouteEnter(to, from ,next) {
    next(async (vm) => {
      const res = await $http.get('ajax/data?path=db');
      vm.data = res.data.data;
    });
  },
  async beforeRouteUpdate(to, from ,next) {
    const res = await $http.get('ajax/data?path=db');
    this.data = res.data.data;
  },
  setup() {
    const data = ref(null);

    const totalTime = computed(() => {
      return data.value?.queries?.reduce((sum, query) => {
        return sum + query.time;
      }, 0) * 1000;
    });

    const totalMemory = computed(() => {
      return data.value?.queries?.reduce((sum, query) => {
        return sum + query.memory;
      }, 0) / 1024 / 1024;
    });

    return {
      data,
      totalTime,
      totalMemory,
      round
    }
  }
};

function round(num) {
  return Math.round(num * 100) / 100;
}
</script>

<style scoped>

</style>
