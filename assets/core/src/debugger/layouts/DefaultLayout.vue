<template>
<div v-if="currentData">
  <div class="p-6 bg-gray-300">
    <h1 class="text-xl font-bold text-gray-800">
      <slot name="title"></slot>
    </h1>
    <div class="text-gray-600">
      {{ currentId }} / <a target="_blank" class="text-gray-600" :href="currentData.url">{{ currentData.url }}</a>
    </div>
  </div>

  <slot></slot>
</div>
</template>

<script>
import { currentData, currentId } from '../services/store.js';

export default {
  name: 'DefaultLayout',
  setup() {
    return {
      currentId,
      currentData
    };
  }
};
</script>

<style scoped>

</style>
