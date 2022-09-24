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

<script>
import { computed, ref } from 'vue';
import EventListenersTable from '../components/events/EventListenersTable.vue';
import DefaultLayout from '../layouts/DefaultLayout.vue';
import $http from '../services/http.js';
export default {
  name: 'Events',
  components: { EventListenersTable, DefaultLayout },
  async beforeRouteEnter(to, from ,next) {
    next(async (vm) => {
      const res = await $http.get('ajax/data?path=events');
      vm.data = res.data.data;
    });
  },
  async beforeRouteUpdate(to, from ,next) {
    const res = await $http.get('ajax/data?path=events');
    this.data = res.data.data;
  },
  setup() {
    const data = ref(null);

    // const events = computed(() => {
    //   const items = [];
    //
    //   for (const eventName in data.value) {
    //     const listeners = data.value[eventName] || {};
    //
    //     for (const listenerName in listeners) {
    //       const count = listeners[listenerName] || 0;
    //
    //       items.push({
    //         event: eventName,
    //         listener: listenerName,
    //         count
    //       });
    //     }
    //   }
    //
    //   return items;
    // });

    // const triggeredEvents = computed(() => {
    //   const items = {};
    //
    //   for (const eventName in data.value) {
    //     const listeners = data.value[eventName] || {};
    //
    //     for (const listenerName in listeners) {
    //       const count = listeners[listenerName] || 0;
    //
    //       if (count > 0) {
    //         items[eventName] = items[eventName] || {};
    //         items[eventName][listenerName] = count;
    //       }
    //     }
    //   }
    //
    //   return items;
    // });
    //
    // const untriggeredEvents = computed(() => {
    //   const items = {};
    //
    //   for (const eventName in data.value) {
    //     const listeners = data.value[eventName] || {};
    //
    //     for (const listenerName in listeners) {
    //       const count = listeners[listenerName] || 0;
    //
    //       if (count <= 0) {
    //         items[eventName] = items[eventName] || {};
    //         items[eventName][listenerName] = count;
    //       }
    //     }
    //   }
    //
    //   return items;
    // });

    return {
      data,
    };
  }
};
</script>

<style scoped>

</style>
