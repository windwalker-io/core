/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

import 'regenerator-runtime';
import App from '@/App.vue';
import { createApp } from 'vue';
import router from './routes.js';

import '../../scss/debugger.scss';

const app = createApp(App)
  .use(router);

app.mount('app');
