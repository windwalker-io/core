<template>
  <div class="p-4">
    <table class="table table-auto">
      <thead>
      <tr>
        <th>
          ID
        </th>
        <th>
          See
        </th>
        <th>
          IP
        </th>
        <th>
          Method
        </th>
        <th>
          URL
        </th>
        <th>
          Time
        </th>
        <th>
          Info
        </th>
      </tr>
      </thead>

      <tbody>
      <tr v-for="item of items">
        <td>
          {{ item.id }}
        </td>
        <td>
          <button class="bg-blue-400 rounded-sm px-3 py-2 text-white hover:bg-blue-500"
            type="button"
            @click="selectId(item.id)">
            <svg style="height: 16px" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="eye" class="svg-inline--fa fa-eye fa-w-18" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M572.5 238.1C518.3 115.5 410.9 32 288 32S57.69 115.6 3.469 238.1C1.563 243.4 0 251 0 256c0 4.977 1.562 12.6 3.469 17.03C57.72 396.5 165.1 480 288 480s230.3-83.58 284.5-206.1C574.4 268.6 576 260.1 576 256C576 251 574.4 243.4 572.5 238.1zM432 256c0 79.45-64.47 144-143.9 144C208.6 400 144 335.5 144 256S208.5 112 288 112S432 176.5 432 256zM288 160C285.7 160 282.4 160.4 279.5 160.8C284.8 170 288 180.6 288 192c0 35.35-28.65 64-64 64C212.6 256 201.1 252.7 192.7 247.5C192.4 250.5 192 253.6 192 256c0 52.1 43 96 96 96s96-42.99 96-95.99S340.1 160 288 160z"></path></svg>
          </button>
        </td>
        <td>
          {{ item.ip }}
        </td>
        <td>
          {{ item.method }}
        </td>
        <td>
          <a href="#"
            class="text-gray-400">
            {{ item.url }}
          </a>
        </td>
        <td>
          {{ item.time }}
        </td>
        <td>
          {{ item.status }}
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { onMounted, reactive, toRefs } from 'vue';
import router from '@/routes.js';
import $http from '@/services/http.js';
import { currentId } from '@/services/store.js';

export default {
  name: 'Dashboard',
  setup() {
    const state = reactive({
      items: [],
    });

    onMounted(async () => {
      const res = await $http.get('ajax/history');

      state.items = res.data.data;
    });

    function selectId(id) {
        currentId.value = id;

        router.push('/system/' + id);
    }

    return {
      ...toRefs(state),
      selectId
    };
  }
};
</script>

<style scoped>

</style>
