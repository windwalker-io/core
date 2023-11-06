

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
