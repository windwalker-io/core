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
            <svg style="height: 14px; display: inline" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="clipboard" class="svg-inline--fa fa-clipboard fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M336 64h-53.88C268.9 26.8 233.7 0 192 0S115.1 26.8 101.9 64H48C21.5 64 0 85.48 0 112v352C0 490.5 21.5 512 48 512h288c26.5 0 48-21.48 48-48v-352C384 85.48 362.5 64 336 64zM192 64c17.67 0 32 14.33 32 32c0 17.67-14.33 32-32 32S160 113.7 160 96C160 78.33 174.3 64 192 64zM272 224h-160C103.2 224 96 216.8 96 208C96 199.2 103.2 192 112 192h160C280.8 192 288 199.2 288 208S280.8 224 272 224z"></path></svg>
            <span class="">Copy</span>
          </button>
          <button
            type="button"
            class="btn btn-primary btn-sm"
          >
            <svg style="height: 14px" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="link" class="svg-inline--fa fa-link fa-w-20" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M598.6 41.41C570.1 13.8 534.8 0 498.6 0s-72.36 13.8-99.96 41.41l-43.36 43.36c15.11 8.012 29.47 17.58 41.91 30.02c3.146 3.146 5.898 6.518 8.742 9.838l37.96-37.96C458.5 72.05 477.1 64 498.6 64c20.67 0 40.1 8.047 54.71 22.66c14.61 14.61 22.66 34.04 22.66 54.71s-8.049 40.1-22.66 54.71l-133.3 133.3C405.5 343.1 386 352 365.4 352s-40.1-8.048-54.71-22.66C296 314.7 287.1 295.3 287.1 274.6s8.047-40.1 22.66-54.71L314.2 216.4C312.1 212.5 309.9 208.5 306.7 205.3C298.1 196.7 286.8 192 274.6 192c-11.93 0-23.1 4.664-31.61 12.97c-30.71 53.96-23.63 123.6 22.39 169.6C293 402.2 329.2 416 365.4 416c36.18 0 72.36-13.8 99.96-41.41L598.6 241.3c28.45-28.45 42.24-66.01 41.37-103.3C639.1 102.1 625.4 68.16 598.6 41.41zM234 387.4L196.1 425.3C181.5 439.1 162 448 141.4 448c-20.67 0-40.1-8.047-54.71-22.66c-14.61-14.61-22.66-34.04-22.66-54.71s8.049-40.1 22.66-54.71l133.3-133.3C234.5 168 253.1 160 274.6 160s40.1 8.048 54.71 22.66c14.62 14.61 22.66 34.04 22.66 54.71s-8.047 40.1-22.66 54.71L325.8 295.6c2.094 3.939 4.219 7.895 7.465 11.15C341.9 315.3 353.3 320 365.4 320c11.93 0 23.1-4.664 31.61-12.97c30.71-53.96 23.63-123.6-22.39-169.6C346.1 109.8 310.8 96 274.6 96C238.4 96 202.3 109.8 174.7 137.4L41.41 270.7c-27.6 27.6-41.41 63.78-41.41 99.96c-.0001 36.18 13.8 72.36 41.41 99.97C69.01 498.2 105.2 512 141.4 512c36.18 0 72.36-13.8 99.96-41.41l43.36-43.36c-15.11-8.012-29.47-17.58-41.91-30.02C239.6 394.1 236.9 390.7 234 387.4z"></path></svg>
          </button>
          <button
            type="button"
            class="btn btn-success btn-sm"
            @click="goToLast('/db')"
          >
            <svg style="height: 14px" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="arrow-rotate-right" class="svg-inline--fa fa-arrow-rotate-right fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M496 48V192c0 17.69-14.31 32-32 32H320c-17.69 0-32-14.31-32-32s14.31-32 32-32h63.39c-29.97-39.7-77.25-63.78-127.6-63.78C167.7 96.22 96 167.9 96 256s71.69 159.8 159.8 159.8c34.88 0 68.03-11.03 95.88-31.94c14.22-10.53 34.22-7.75 44.81 6.375c10.59 14.16 7.75 34.22-6.375 44.81c-39.03 29.28-85.36 44.86-134.2 44.86C132.5 479.9 32 379.4 32 256s100.5-223.9 223.9-223.9c69.15 0 134 32.47 176.1 86.12V48c0-17.69 14.31-32 32-32S496 30.31 496 48z"></path></svg>
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
            <svg style="height: 14px; fill: white;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M40 48C26.7 48 16 58.7 16 72v48c0 13.3 10.7 24 24 24H88c13.3 0 24-10.7 24-24V72c0-13.3-10.7-24-24-24H40zM192 64c-17.7 0-32 14.3-32 32s14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zM16 232v48c0 13.3 10.7 24 24 24H88c13.3 0 24-10.7 24-24V232c0-13.3-10.7-24-24-24H40c-13.3 0-24 10.7-24 24zM40 368c-13.3 0-24 10.7-24 24v48c0 13.3 10.7 24 24 24H88c13.3 0 24-10.7 24-24V392c0-13.3-10.7-24-24-24H40z"/></svg>
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
