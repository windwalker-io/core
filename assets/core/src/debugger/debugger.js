
import 'regenerator-runtime';

import App from '@/App.vue';
import '@asika32764/vue-animate/dist/vue-animate.min.css';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { createApp } from 'vue';
import '../../scss/debugger.scss';
import './font-awesome.js';
import 'bootstrap/dist/js/bootstrap.js';
import router from './routes.js';

const app = createApp(App)
  .use(router)
  .component('fa-icon', FontAwesomeIcon);

app.mount('app');
