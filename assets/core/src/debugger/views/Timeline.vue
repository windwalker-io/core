<template>
<DefaultLayout>
  <template #title>
    Timeline
  </template>

  <div class="p-4" v-if="data">

    <section class="l-section l-section--system">
      <h3>System Timeline</h3>

      <TimelineTable :items="systemItems" />
    </section>

    <section class="l-section l-section--profilers mt-5">

      <ul class="nav nav-pills" id="profilers-tab" role="tablist">
        <li class="nav-item" role="presentation"
          v-for="(instance, i) of instances">
          <button class="nav-link active" id="home-tab" data-bs-toggle="tab"
            :data-bs-target="`tab-${instance}`"
            type="button"
            role="tab"
            aria-selected="true">
            {{ instance }}
          </button>
        </li>
      </ul>
      <div class="tab-content mt-4" id="myTabContent">
        <div class="tab-pane fade "
          v-for="(instance, i) of instances"
          :class="[ i === 0 ? 'show active' : '' ]"
          :id="`tab-${instance}`"
          role="tabpanel"
          tabindex="0">
          <div class="">
            <TimelineTable :items="data[instance].items" />
          </div>
        </div>
      </div>

    </section>
  </div>
</DefaultLayout>
</template>

<script>
import { computed, ref } from 'vue';
import TimelineTable from '../components/timeline/TimelineTable.vue';
import DefaultLayout from '../layouts/DefaultLayout.vue';
import $http from '../services/http.js';
export default {
  name: 'Timeline',
  components: { TimelineTable, DefaultLayout },
  async beforeRouteEnter(to, from ,next) {
    next(async (vm) => {
      const res = await $http.get('ajax/data?path=profiler');
      vm.data = res.data.data;
    });
  },
  setup() {
    const data = ref(null);

    const instances = computed(() => Object.keys(data.value));
    const systemItems = computed(() => {
      if (!data.value) {
        return [];
      }

      return data.value.main.items.filter((item) => {
        const tags = item.tags || [];

        return tags.indexOf('system') !== -1;
      }) || [];
    });

    return {
      data,
      instances,
      systemItems,
    };
  }
};
</script>

<style scoped>

</style>
