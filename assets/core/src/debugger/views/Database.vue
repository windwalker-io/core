<template>
  <defaultLayout>
    <template #title>
      Database
    </template>

    <div v-if="data" class="p-4">
      <!-- DB info -->

      <div>
        <h4>Queries</h4>

        <div class="my-3">
          Count: <span class="badge bg-info">{{ data?.queries?.length || 0 }}</span>
          -
          Time:
          <span class="badge" :class="`bg-${stateColor(totalTime, 15 * (data?.queries?.length || 0))}`">
            {{ round(totalTime) }}ms
          </span>
          -
          Memory:
          <span class="badge" :class="`bg-${stateColor(totalMemory, 0.05 * (data?.queries?.length || 0))}`">
            {{ round(totalMemory) }}MB
          </span>
        </div>
      </div>

      <div class="mt-5">
        <div class="mb-4" v-for="(query, i) of data.queries">
          <QueryInfo :item="query" :i="i + 1"
            :total-count="data?.queries?.length || 0"
            :total-time="totalTime"
            :total-memory="totalMemory"
            @open-backtrace="openBacktrace"
          />
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

    const totalTime = computed(() => {
      return data.value?.queries?.reduce((sum, query) => {
        return sum + query.time;
      }, 0) * 1000;
    });

    const totalMemory = computed(() => {
      return data.value?.queries?.reduce((sum, query) => {
        return sum + query.memory;
      }, 0) / 1024 / 1024;
    });

    const showBacktraceModal = ref(false);
    const backtrace = ref([]);
    const backtraceIndex = ref(0);

    function openBacktrace(trace, i) {
      console.log(i);
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

    return {
      data,
      totalTime,
      totalMemory,
      showBacktraceModal,
      backtrace,
      backtraceIndex,

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
