import { createApp } from 'vue'
import App from './App.vue'
import { Test } from '@/router/test';
import { routes } from '@/router';
import store from './store'

console.log(routes, store);
console.log(Test);

createApp(App).mount('#app')
