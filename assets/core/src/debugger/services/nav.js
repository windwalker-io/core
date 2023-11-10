

import router from '../routes.js';
import $http from './http.js';

export async function goToLast(currentRoute = undefined) {
  const res = await $http.get('ajax/last');
  let route = '';

  if (currentRoute) {
    route = currentRoute + '/' + res.data.data;
  } else {
    route = '/system/' + res.data.data;
  }

  return router.push(route);
}
