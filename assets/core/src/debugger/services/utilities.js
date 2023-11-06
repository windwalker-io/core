

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

export function httpStatusColor(status) {
  if (status >= 300 && status < 400) {
    return 'info';
  }

  if (status >= 400 && status < 500) {
    return 'warning';
  }

  if (status >= 200 && status < 300) {
    return 'success';
  }

  return 'danger';
}
