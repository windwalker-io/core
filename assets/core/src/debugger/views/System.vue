<template>
  <DefaultLayout>
    <template #title>
      System
    </template>

    <div class="p-4">
      <div>
        <h4>Windwalker</h4>

        <table class="table table-bordered">
          <tbody>
          <tr>
            <th style="width: 25%" class="">Framework Version</th>
            <td>{{ data.framework_version }}</td>
          </tr>
          <tr>
            <th class="">Core Version</th>
            <td>{{ data.core_version }}</td>
          </tr>
          <tr>
            <th class="border-right">PHP Version</th>
            <td>{{ data.php_version }}</td>
          </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-5">
        <h4>Debug Messages</h4>

        <table class="table table-bordered">
          <thead>
          <tr>
            <th>Type</th>
            <th>Message</th>
          </tr>
          </thead>
          <tbody>
          <template v-for="(msgs, type) of data.messages" :key="msgs">
            <tr v-for="msg of msgs" :key="msg">
              <td style="width: 20%;" class="text-nowrap">
                {{ type }}
              </td>
              <td>
                {{ msg }}
              </td>
            </tr>
          </template>
          </tbody>
        </table>
      </div>

      <div class="mt-5">
        <h4>Config</h4>

        <pre class="bg-light p-3"><code>{{ JSON.stringify(data.config, null, 2) }}</code></pre>
      </div>
    </div>
  </DefaultLayout>
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
  async beforeRouteUpdate(to, from ,next) {
    const res = await $http.get('ajax/data?path=system');
    this.data = res.data.data;
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
