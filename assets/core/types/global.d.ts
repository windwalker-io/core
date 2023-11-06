import '@windwalker-io/unicorn';
import 'systemjs';
import 'jquery';
import 'axios';
import boostrap from 'bootstrap';
import * as vue from 'vue';

declare global {
  var S:  typeof System;
  var bootstrap: typeof boostrap;
  var Vue: typeof vue;
}
