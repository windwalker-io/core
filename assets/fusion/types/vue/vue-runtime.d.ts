/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

import Vue from 'vue'

declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    $lang: Function;
    questionProp: Function;
    introtext: Function;
  }
}
