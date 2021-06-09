/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

import { createRouter, createWebHashHistory } from 'vue-router';
import $http from './services/http.js';
import { currentData, currentId } from './services/store.js';

const routes = [
  { path: '/', component: () => import('./views/Dashboard.vue') },
  { path: '/system/:id?', component: () => import('./views/System.vue') },
  { path: '/request/:id?', component: () => import('./views/Request.vue') },
  { path: '/db/:id?', component: () => import('./views/Database.vue') },
];

const router = createRouter({
  // 4. Provide the history implementation to use. We are using the hash history for simplicity here.
  history: createWebHashHistory(),
  routes,
});

router.beforeEach(async (to, from) => {
  if (to.path !== '/' && !to.params.id) {
    if (currentId.value) {
      return to.path + '/' + currentId.value;
    } else {
      return '/';
    }
  }

  if (to.params.id) {
    if (currentId.value !== to.params.id) {
      currentId.value = to.params.id;

      const params = new URLSearchParams({
        'path[url]': 'http.systemUri.full',
        'path[status]': 'http.response.status',
      });
      const res = await $http.get('ajax/data?' + params.toString());

      currentData.value = res.data.data;
    }
  }
});

// router.afterEach((to, from, next) => {
//   console.log('After', to);
// });

export default router;
