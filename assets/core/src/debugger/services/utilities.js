/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2022 __ORGANIZATION__.
 * @license    __LICENSE__
 */

export function stateColor(value, avg) {
  if (value > (avg * 2)) {
    return 'danger';
  } else if (value > (avg * 1.5)) {
    return 'warning';
  } else if (value < (avg / 2)) {
    return 'success';
  } else {
    return 'info';
  }
}
