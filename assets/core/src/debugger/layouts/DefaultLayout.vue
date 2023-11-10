<template>
<div v-if="currentData">
  <div class="p-3 mt-3 mx-4 rounded-3" style="background-color: var(--bs-gray-800);">
    <h1 class="text-xl font-bold text-gray-800">
      <slot name="title"></slot>
    </h1>
    <div class="text-muted">
      <router-link to="/" class="btn btn-sm btn-primary">
        <fa-icon icon="fa-solid fa-list"></fa-icon>
      </router-link>
      <button type="button" @click="goToLast('/' + route.name)" class="btn btn-sm btn-success">
        <fa-icon icon="fa-solid fa-arrows-rotate"></fa-icon>
      </button>
      /
      {{ currentId }}
      / <a target="_blank" class="text-gray-600" :href="currentData.url">{{ currentData.url }}</a>
    </div>
  </div>

  <slot></slot>
</div>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { goToLast } from '../services/nav';
import { currentData, currentId } from '../services/store';

const route = useRoute();
</script>

<style scoped>

</style>
