<template>
  <div :id="`query-${i}`" class="card rounded-3 border border-1 border-primary">
    <div class="card-header d-flex justify-content-between">
      <h4 class="m-0">
        Query: {{ i }}
      </h4>

      <div class="d-flex align-items-center justify-content-between">
        <div class="text-muted">
          <button
            type="button"
            class="btn btn-outline-primary btn-sm"
            @click="copy"
          >
            <fa-icon icon="fa fa-clipboard" />
            <span class="">Copy</span>
          </button>
          <button
            type="button"
            class="btn btn-primary btn-sm"
          >
            <fa-icon icon="fa fa-link" />
          </button>
          <button
            type="button"
            class="btn btn-success btn-sm"
            @click="goToLast('/db')"
          >
            <fa-icon icon="fa fa-rotate-right" />
          </button>
        </div>
      </div>
    </div>
    <div class="card-body">

      <div class="">
        <pre style="word-break: break-all; white-space: pre-wrap;"
          class="border p-4"
          v-html="formatQuery(item.debug_query)"
          ></pre>
      </div>
      <div class="py-4 d-flex justify-content-between">
        <div>
          Query Time:
          <span class="badge" :class="`bg-${stateColor(item.time * 1000, 15)}`">
            {{ round(item.time * 1000) }}ms
          </span>
          Memory:
          <span class="badge" :class="`bg-${stateColor(item.memory / 1024 / 1024, 0.05)}`">
            {{ round(item.memory / 1024 / 1024) }}MB
          </span>
          Return Rows
          <span class="badge bg-info rounded-pill">
            {{ item.count }}
          </span>
        </div>

        <div>
          <button class="btn btn-primary" @click="openBacktrace(item.backtrace, i)">
            <fa-icon icon="fa fa-list" />
            Backtrace
          </button>
        </div>
      </div>
    </div>

    <div v-if="item.explain">
      <table class="table">
        <thead>
        <tr>
          <th class="">
            ID
          </th>
          <th class="">
            Select Type
          </th>
          <th class="">
            Table
          </th>
          <th class="">
            Type
          </th>
          <th class="">
            Possible Keys
          </th>
          <th class="">
            Key
          </th>
          <th class="">
            Key Length
          </th>
          <th class="">
            Reference
          </th>
          <th class="">
            Rows
          </th>
          <th class="">
            Extra
          </th>
        </tr>
        </thead>
        <tbody class="bg-white">
        <tr v-for="explain of item.explain">
          <td class="">
            {{ explain.id }}
          </td>
          <td class="">
            {{ explain.select_type }}
          </td>
          <td class="">
            {{ explain.table }}
          </td>
          <td class="">
            {{ explain.type }}
          </td>
          <td class="text-wrap">
            <div style="word-break: break-all">
              {{ explain.possible_keys }}
            </div>
          </td>
          <td class="">
            {{ explain.key }}
          </td>
          <td class="">
            {{ explain.key_len }}
          </td>
          <td class="">
            {{ explain.ref }}
          </td>
          <td class="">
            {{ explain.rows }}
          </td>
          <td class="">
            {{ explain.Extra }}
          </td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import { format } from 'sql-formatter';
import { stateColor } from '../../services/utilities';
import { goToLast } from '../../services/nav';

export default {
  name: 'query-info',
  props: {
    i: Number,
    item: Object,
    totalCount: Number,
    totalTime: Number,
    totalMemory: Number,
  },
  setup(props, { emit }) {
    function copy() {
      navigator.clipboard.writeText(props.item.debug_query);
    }

    function openBacktrace(backtrace) {
      emit('open-backtrace', backtrace, props.i);
    }

    return {
      formatQuery,
      round,
      goToLast,
      copy,
      stateColor,
      openBacktrace
    };
  }
};

function formatQuery(query) {
  try {
    return format(query, {
      keywordCase: 'upper',
      
    }).replace(/\n/, '<br>');
  } catch (e) {
    console.error(e);
    return query;
  }
}

function round(num) {
  return Math.round(num * 100) / 100;
}
</script>

<style scoped>

</style>
