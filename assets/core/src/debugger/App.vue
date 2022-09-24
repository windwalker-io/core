<template>
  <div class="c-main-wrapper">
    <div class="navbar navbar-expand-lg fixed-top navbar-dark bg-dark"
      style="height: 60px">
      <div class="container-fluid">
        <!-- Logo -->
        <div class="navbar-brand">
          <img src="assets/vendor/@windwalker-io/core/dist/images/windwalker-logo-h-w.svg" alt="logo"
            style="height: 30px">
        </div>

        <!-- Header  -->
        <div class="">

        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="d-flex flex-column flex-md-row" style="margin-top: 60px;">
      <!-- Sidebar -->
      <div class="h-w-sidebar bg-light">
        <div class="nav flex-column bg-light position-sticky"
          style="min-width: 200px; top: 60px">
          <div class="nav-item nav-item--dashboard">
            <router-link to="/"
              class="c-sidebar-menu-item nav-link"
              :class="{ active: currentRoute === 'dashboard' }">
              Dashboard
            </router-link>
          </div>
          <div class="nav-item nav-item--system">
            <router-link to="/system"
              class="c-sidebar-menu-item nav-link"
              :class="{ active: currentRoute === 'system' }">
              System
            </router-link>
          </div>
          <div class="nav-item nav-item--request">
            <router-link to="/request"
              class="c-sidebar-menu-item nav-link"
              :class="{ active: currentRoute === 'request' }">
              Request
            </router-link>
          </div>
          <div class="nav-item nav-item--routing">
            <router-link to="/routing"
              class="c-sidebar-menu-item nav-link"
              :class="{ active: currentRoute === 'routing' }">
              Routing
            </router-link>
          </div>
          <div class="nav-item nav-item--timeline">
            <router-link to="/timeline"
              class="c-sidebar-menu-item nav-link"
              :class="{ active: currentRoute === 'timeline' }">
              Timeline
            </router-link>
          </div>
          <div class="nav-item nav-item--timeline">
            <router-link to="/events"
              class="c-sidebar-menu-item nav-link"
              :class="{ active: currentRoute === 'events' }">
              Events
            </router-link>
          </div>
          <div class="nav-item nav-item--db">
            <router-link to="/db"
              class="c-sidebar-menu-item nav-link"
              :class="{ active: currentRoute === 'db' }">
              Database
            </router-link>
          </div>
        </div>
      </div>

      <!-- Content Body -->
      <div class="flex-grow-1">
        <router-view></router-view>
      </div>
    </div>

    <store />
  </div>
</template>

<script>
import { computed } from 'vue';
import { RouterLink, RouterView, useRoute } from 'vue-router';
import Store from './Store.vue';

export default {
  name: 'App',
  components: {
    Store,
    RouterLink,
    RouterView
  },
  setup() {
    const route = useRoute();
    const currentRoute = computed(() => route.name);

    return {
      currentRoute
    };
  }
};
</script>

<style scoped lang="scss">
.c-sidebar-menu-item.active {

}
</style>
