<template>
  <defaultLayout>
    <template #title>
      Database
    </template>

    <div v-if="data" class="p-4">
      <!-- DB info -->

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

          <div>
            <h4>Queries</h4>

            <div class="my-3">
              Count: <span class="badge bg-info">{{ data?.queries[instance]?.length || 0 }}</span>
              -
              Time:
              <span class="badge" :class="`bg-${stateColor(totalTime(instance), 15 * (data?.queries[instance]?.length || 0))}`">
            {{ round(totalTime(instance)) }}ms
          </span>
              -
              Memory:
              <span class="badge" :class="`bg-${stateColor(totalMemory(instance), 0.05 * (data?.queries[instance]?.length || 0))}`">
            {{ round(totalMemory(instance)) }}MB
          </span>
            </div>
          </div>

          <div class="mt-5">
            <div class="mb-4" v-for="(query, i) of data.queries[instance]">
              <QueryInfo :item="query" :i="i + 1"
                :total-count="data?.queries[instance]?.length || 0"
                :total-time="totalTime(instance)"
                :total-memory="totalMemory(instance)"
                @open-backtrace="openBacktrace"
              />
            </div>
          </div>

        </div>
      </div>

      <BsModal :open="showBacktraceModal" @hidden="showBacktraceModal = false"
        :title="`Query ${backtraceIndex}: Backtrace`" size="xl">
        <table class="table table-striped table-bordered">
          <tbody>
          <tr v-for="traceItem of backtrace" style="font-family: monospace; font-size: 13px; word-break: break-all;">
            <td>
              {{ traceItem.function }}
            </td>
            <td>
              <a :href="getEditorLink(traceItem)">
                {{ replaceRoot(traceItem.pathname) }}
              </a>
            </td>
          </tr>
          </tbody>
        </table>
      </BsModal>
    </div>
  </defaultLayout>
</template>

<script>
import { computed, ref } from 'vue';
import BsModal from '@/components/BsModal.vue';
import QueryInfo from '@/components/db/QueryInfo.vue';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import $http from '@/services/http.js';
import { stateColor } from '../services/utilities.js';

export default {
  name: 'Database',
  components: { BsModal, QueryInfo, DefaultLayout },
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

    function totalTime(instance) {
      return data.value?.queries[instance]?.reduce((sum, query) => {
        return sum + query.time;
      }, 0) * 1000;
    }

    function totalMemory(instance) {
      return data.value?.queries[instance]?.reduce((sum, query) => {
        return sum + query.memory;
      }, 0) / 1024 / 1024;
    }

    const showBacktraceModal = ref(false);
    const backtrace = ref([]);
    const backtraceIndex = ref(0);

    function openBacktrace(trace, i) {
      backtrace.value = trace;
      backtraceIndex.value = i;
      showBacktraceModal.value = true;
    }

    const editor = document.__data.editor;
    const sysPath = document.__data.systemPath;

    function getEditorLink(trace) {
      return `${editor}://open?file=${trace.pathname}&line=${trace.line}`;
    }

    function replaceRoot(path) {
      return path.replace(sysPath, 'ROOT');
    }

    const instances = computed(() => data.value.connections.map((conn) => conn.name));

    return {
      data,
      totalTime,
      totalMemory,
      showBacktraceModal,
      backtrace,
      backtraceIndex,
      instances,

      openBacktrace,
      getEditorLink,

      stateColor,
      round,
      replaceRoot,
    }
  }
};

function round(num) {
  return Math.round(num * 100) / 100;
}
</script>

<style scoped>

</style>
