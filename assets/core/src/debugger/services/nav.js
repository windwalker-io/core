/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

import router from '../routes.js';
import $http from './http.js';

export async function goToLast(currentRoute = null) {
  const res = await $http.get('ajax/last');
  let route = '';

  if (currentRoute) {
    route = currentRoute += '/' + res.data.data;
  } else {
    route = '/system/' + res.data.data;
  }

  return router.push(route);
}
