<template>
  <div class="c-main-wrapper min-h-screen	flex flex-col">
    <div class="flex items-center bg-gray-800 p-3 text-white"
      style="height: 60px">
      <!-- Logo -->
      <div class="h-w-sidebar">
        <img src="assets/vendor/@windwalker-io/core/dist/images/windwalker-logo-h-w.svg" alt="logo"
          style="height: 30px">
      </div>

      <!-- Header  -->
      <div class="">

      </div>
    </div>

    <!-- Body -->
    <div class="flex flex-grow">
      <!-- Sidebar -->
      <div class="h-w-sidebar bg-gray-200">
        <div class="">
          <router-link to="/"
          class="c-sidebar-menu-item">
            Dashboard
          </router-link>
        </div>
        <div>
          <router-link to="/system"
          class="c-sidebar-menu-item">
            System
          </router-link>
        </div>
        <div>
          <router-link to="/request"
            class="c-sidebar-menu-item">
            Request
          </router-link>
        </div>
        <div>
          <router-link to="/db"
            class="c-sidebar-menu-item">
            Database
          </router-link>
        </div>
      </div>

      <!-- Content Body -->
      <div class="bg-gray-100 flex-grow">
        <router-view></router-view>
      </div>
    </div>

    <store />
  </div>
</template>

<script>
import { RouterLink, RouterView } from 'vue-router';
import Store from './Store.vue';

export default {
  name: 'App',
  components: {
    Store,
    RouterLink,
    RouterView
  },
  setup() {
    return {
      
    };
  }
};
</script>

<style scoped>

</style>
