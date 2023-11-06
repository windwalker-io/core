<template>
<DefaultLayout>
  <template #title>
    Events
  </template>

  <div class="p-4" v-if="data">

    <section class="l-section l-section--triggered">
      <h3>Event Triggered</h3>

      <EventListenersTable :events="data.invoked" />
    </section>

    <section class="l-section l-section--untriggered mt-5">
      <h3>Event Not Triggered (But has Listeners)</h3>

      <EventListenersTable :events="data.uninvoked" />
    </section>

  </div>
</DefaultLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import EventListenersTable from '../components/events/EventListenersTable.vue';
import DefaultLayout from '../layouts/DefaultLayout.vue';
import $http from '../services/http.js';

const data = ref(null);

const res = await $http.get('ajax/data?path=events');
data.value = res.data.data;

</script>

<style scoped>

</style>
