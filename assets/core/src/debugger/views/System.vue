<template>
  <default-layout>
    <template #title>
      System
    </template>

    <div class="p-4">
      <div>
        <h4>Windwalker</h4>

        <table class="table w-full border">
          <tr>
            <th style="width: 25%" class="border-right">Framework Version</th>
            <td>{{ data.framework_version }}</td>
          </tr>
          <tr>
            <th class="border-right">Core Version</th>
            <td>{{ data.core_version }}</td>
          </tr>
          <tr>
            <th class="border-right">PHP Version</th>
            <td>{{ data.php_version }}</td>
          </tr>
        </table>
      </div>

      <hr />

      <div class="mt-4">
        <h4>Config</h4>

        <pre class="bg-gray-200 p-3 rounded-sm text-sm">{{ JSON.stringify(data.config, null, 2) }}</pre>
      </div>
    </div>
  </default-layout>
</template>

<script>
import { onMounted, ref } from 'vue';
import { onBeforeRouteUpdate, useRoute } from 'vue-router';
import DefaultLayout from '../layouts/DefaultLayout.vue';
import $http from '../services/http.js';

export default {
  name: 'System',
  components: { DefaultLayout },
  async beforeRouteEnter(to, from ,next) {
    next(async (vm) => {
      const res = await $http.get('ajax/data?path=system');
      vm.data = res.data.data;
    });
  },
  setup() {
    const data = ref({});

    return {
      data
    }
  }
};
</script>

<style scoped>

</style>
