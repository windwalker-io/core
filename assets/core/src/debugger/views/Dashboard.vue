<template>
  <div class="p-4">
    <table class="table table-striped table-bordered">
      <thead class="table-dark">
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
          <a href="#" @click.prevent="selectId(item.id)" class="">
            {{ item.id }}
          </a>
        </td>
        <td>
          <button class="btn btn-primary btn-sm"
            type="button"
            @click="selectId(item.id)">
            <fa-icon icon="fa-solid fa-eye"></fa-icon>
          </button>
        </td>
        <td>
          {{ item.ip }}
        </td>
        <td>
          <div>
            {{ item.method }}
          </div>
          <div>
            <span v-if="item.ajax" class="badge bg-danger">
              AJAX | API
            </span>
          </div>
        </td>
        <td style="word-break: break-all">
          <a :href="item.url"
            target="_blank"
            class="link-secondary">
            {{ item.url }}
            <fa-icon class="small" icon="fa-solid fa-external-link"></fa-icon>
          </a>
        </td>
        <td>
          {{ dateFormat(item.time) }}
        </td>
        <td>
          <span class="badge" :class="`bg-${httpStatusColor(item.response?.status || 0)}`">
            {{ item.response?.status }}
          </span>
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import * as dayjs from 'dayjs';
import { onMounted, reactive, ref, toRefs } from 'vue';
import router from '../routes.js';
import $http from '../services/http.js';
import { currentId } from '../services/store.js';
import { httpStatusColor } from '../services/utilities.js';

const state = reactive({
  items: [],
});

const items = ref([]);

onMounted(async () => {
  const res = await $http.get('ajax/history');

  items.value = res.data.data;
});

function selectId(id) {
  router.push('/system/' + id);
}

function dateFormat(ts) {
  return dayjs.unix(ts).format('YYYY-MM-DD HH:mm:ssZ');
}
</script>

<style scoped>

</style>
