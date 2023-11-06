<template>
<DefaultLayout>
  <template #title>
    Routing
  </template>

  <div class="p-4" v-if="data">

    <section class="l-section l-section--info">
      <h3 class="mb-3">Route Information</h3>

      <table class="table table-bordered">
        <tbody>
        <!-- Method -->
        <tr>
          <th style="width: 20%">
            Request Method
          </th>
          <td>
            {{ data.request.method }}
          </td>
        </tr>

        <!-- Routes -->
        <tr>
          <th>
            Total Routes
          </th>
          <td>
            {{ Object.keys(data.routing.routes).length }}
          </td>
        </tr>

        <!-- Matched -->
        <tr>
          <th>
            Matched Route
          </th>
          <td>
            <pre class="m-0">{{ data.routing.matched?.name }}</pre>
          </td>
        </tr>

        <!-- Controller -->
        <tr>
          <th>
            Controller / View
          </th>
          <td>
            <pre class="m-0">{{ data.routing.controller }}</pre>

            <div v-if="data.routing.matched?.options?.vars?.view"
              class="ps-3 mt-2">
              <pre class="m-0"><strong>View:</strong> {{ data.routing.matched?.options?.vars?.view }}</pre>
            </div>
          </td>
        </tr>

        <!-- Handler -->
        <tr>
          <th>
            Handler
          </th>
          <td>
            <pre class="m-0">{{ JSON.stringify(data.routing.matched?.options?.handlers, null, 2) }}</pre>
          </td>
        </tr>

        <!-- Middlewares -->
        <tr>
          <th>
            Middlewares
          </th>
          <td>
            <div v-for="middleware of data.routing?.matched?.options?.middlewares">
              <pre class="mb-1">{{ middleware }}</pre>
            </div>
          </td>
        </tr>
        </tbody>
      </table>
    </section>

    <section class="l-section l-section--uri mt-5">
      <h3 class="mb-3">Uri Information</h3>

      <KeyValueTable :data="data.uri" />
    </section>

    <section class="l-section l-section--routes mt-5">
      <h3 class="mb-3">Routes</h3>

      <table class="table table-dark table-bordered">
        <thead>
          <tr>
            <th>
              Route Name
            </th>
            <th>
              Pattern
            </th>
            <th>
              Methods
            </th>
            <th>
              Controller / View
            </th>
            <!--<th>-->
            <!--  Detail-->
            <!--</th>-->
          </tr>
        </thead>

        <tbody>
        <tr v-for="(route, name) of data.routing.routes"
          :class="{ 'table-primary': data.routing.matched?.name === route.name }">
          <td>
            <code>{{ route.name }}</code>
          </td>
          <td>
            <code>{{ route.options.pattern }}</code>
          </td>
          <td>
            {{ route.options?.method?.join('|') || 'Any' }}
          </td>
          <td style="max-width: 400px">
            <div style="overflow-x: auto">
              <div v-if="route?.options?.vars?.view"
                class="">
                <pre class="m-0">{{ route?.options?.vars?.view }}</pre>
              </div>
              <div v-else>
                <pre>{{ getHandler(route.options.handlers || {}) }}</pre>
              </div>
            </div>
          </td>
          <!--<td></td>-->
        </tr>
        </tbody>
      </table>

    </section>

  </div>
</DefaultLayout>
</template>

<script>
import { ref } from 'vue';
import KeyValueTable from '../components/KeyValueTable.vue';
import DefaultLayout from '../layouts/DefaultLayout.vue';
import $http from '../services/http.js';
export default {
  name: 'Routing',
  components: { KeyValueTable, DefaultLayout },
  async beforeRouteEnter(to, from ,next) {
    next(async (vm) => {
      const params = new URLSearchParams();
      params.set('path[request]', 'http::request');
      params.set('path[uri]', 'http::systemUri');
      params.set('path[routing]', 'routing');

      const res = await $http.get('ajax/data?' + params.toString());
      vm.data = res.data.data;
    });
  },
  async beforeRouteUpdate(to, from ,next) {
    const params = new URLSearchParams();
    params.set('path[request]', 'http::request');
    params.set('path[uri]', 'http::systemUri');
    params.set('path[routing]', 'routing');

    const res = await $http.get('ajax/data?' + params.toString());
    vm.data = res.data.data;
  },
  setup() {
    const data = ref(null);

    function getHandler(handlers) {
      if (handlers['*']) {
        return getCallable(handlers['*']);
      }

      return getCallable(Object.values(handlers)[0]);
    }

    function getCallable(callable) {
      if (!callable) {
        return '-';
      }

      if (typeof callable === 'string') {
        return callable;
      }

      return callable.join('::') + '()';
    }

    return {
      data,

      getHandler
    };
  }
};
</script>

<style scoped>

</style>
