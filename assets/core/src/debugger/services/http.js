

import axios, { AxiosError } from 'axios';
import { currentId } from './store.js';

const $http = axios.create({
  baseURL: '_debugger/',
  timeout: 5000,
});

$http.interceptors.request.use(async config => {
  config.params = config.params || {};
  config.params.id = currentId.value;

  return config;
});

export default $http;
