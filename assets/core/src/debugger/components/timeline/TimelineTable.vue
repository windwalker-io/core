<template>
<div>
  <table class="table table-bordered">
    <thead>
    <tr>
      <th>Label</th>
      <th class="text-end">Total Time</th>
      <th class="text-end">Time</th>
      <th class="text-end">Total Memory</th>
      <th class="text-end">Memory</th>
    </tr>
    </thead>

    <tbody>
    <tr v-for="item of items">
      <td>
        {{ item.label }}
      </td>
      <td class="text-end">
        <span class="badge bg-secondary">
          {{ round(item.endTime) }}ms
        </span>
      </td>
      <td class="text-end" v-if="getTimeOffset(item.endTime) || true">
        <span class="badge" :class="'bg-' + stateColor(getCurrentTimeOffset(), 50)">
          {{ round(getCurrentTimeOffset()) }}ms
        </span>
      </td>
      <td class="text-end">
        <span class="badge bg-secondary">
          {{ round(bytesToMB(item.memory)) }}MB
        </span>
      </td>
      <td class="text-end" v-if="getMemoryOffset(item.memory) || true">
        <span class="badge" :class="'bg-' + stateColor(bytesToMB(getCurrentMemoryOffset()), 2)">
          {{ round(bytesToMB(getCurrentMemoryOffset())) }}MB
        </span>
      </td>
    </tr>
    </tbody>
  </table>
</div>
</template>

<script>
import { stateColor } from '../../services/utilities.js';

export default {
  name: 'TimelineTable',
  props: {
    items: Array
  },
  setup() {
    let lastTime = 0;
    let timeOffset = 0;

    function getTimeOffset(time) {
      timeOffset = time - lastTime;

      lastTime = time;

      return timeOffset;
    }

    function getCurrentTimeOffset() {
      return timeOffset;
    }

    let lastMemory = 0;
    let memoryOffset = 0;

    function getMemoryOffset(memory) {
      memoryOffset = memory - lastMemory;

      lastMemory = memory;

      return memoryOffset;
    }

    function getCurrentMemoryOffset() {
      return memoryOffset;
    }

    function bytesToMB(value) {
      return value / 1024 / 1024;
    }

    function round(num) {
      return Math.round(num * 10000) / 10000;
    }

    return {
      getTimeOffset,
      getCurrentTimeOffset,
      getMemoryOffset,
      getCurrentMemoryOffset,
      stateColor,
      bytesToMB,
      round
    };
  }
};
</script>

<style scoped>

</style>
