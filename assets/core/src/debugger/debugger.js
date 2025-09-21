import('@asika32764/vue-animate/dist/vue-animate.min.css');
import('../../scss/debugger.scss');

import App from './App.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { createApp } from 'vue';
import './font-awesome.js';
import 'bootstrap/dist/js/bootstrap.js';
import router from './routes.js';

const app = createApp(App)
  .use(router)
  .component('fa-icon', FontAwesomeIcon);

app.mount('app');
