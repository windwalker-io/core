<template>
  <DefaultLayout>
    <template #title>
      Request
    </template>

    <div ref="root" class="p-4" v-if="data">
      <section class="l-section l-section--get mt-5">
        <h3>GET Variables</h3>

        <KeyValueTable :data="data.request.query" />

      </section>

      <section class="l-section l-section--body mt-5">
        <h3>Body Variables</h3>

        <KeyValueTable :data="data.request.body" />

      </section>

      <section class="l-section l-section--files mt-5">
        <h3>FILES Variables</h3>

        <KeyValueTable :data="data.request.files" />

      </section>

      <section class="l-section l-section--session mt-5">
        <h3>Session Variables</h3>

        <KeyValueTable :data="data.session" />

      </section>

      <section class="l-section l-section--cookies mt-5">
        <h3>Cookies Variables</h3>

        <KeyValueTable :data="data.cookies || data.request.cookies" />

      </section>

      <section class="l-section l-section--server mt-5">
        <h3>SERVER Variables</h3>

        <KeyValueTable :data="data.request.server" />

      </section>

      <section class="l-section l-section--env mt-5">
        <h3>ENV Variables</h3>

        <KeyValueTable :data="data.request.env" />

      </section>
    </div>

    <teleport to=".nav-item--request">
      <ul class="nav ps-4 small">
        <li class="nav-item">
          <a href="#" class="nav-link" @click.prevent="goto('get')">
            GET Variables
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" @click.prevent="goto('body')">
            Body Variables
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" @click.prevent="goto('files')">
            Files Variables
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" @click.prevent="goto('session')">
            Session Variables
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" @click.prevent="goto('cookies')">
            Cookies Variables
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" @click.prevent="goto('server')">
            SERVER Variables
          </a>
        </li>
        <li class="nav-item">
          <a href="#" class="nav-link" @click.prevent="goto('env')">
            ENV Variables
          </a>
        </li>
      </ul>
    </teleport>
  </DefaultLayout>
</template>

<script>
import { ref } from 'vue';
import KeyValueTable from '../components/KeyValueTable.vue';
import DefaultLayout from '../layouts/DefaultLayout.vue';
import $http from '../services/http.js';

export default {
  name: 'Request',
  components: { KeyValueTable, DefaultLayout },
  async beforeRouteEnter(to, from ,next) {
    next(async (vm) => {
      const res = await $http.get('ajax/data?path=http');
      vm.data = res.data.data;
    });
  },
  setup() {
    const data = ref(null);
    const root = ref(null);

    function goto(section) {
      const sec = root.value.querySelector(`.l-section--${section}`);

      if (sec) {
        window.scrollTo({
          top: sec.offsetTop - 100,
          behavior: 'smooth'
        });
      }
    }

    return {
      data,
      root,
      goto,
    }
  }
};
</script>

<style scoped>

</style>
