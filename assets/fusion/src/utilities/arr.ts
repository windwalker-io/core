import { MaybeArray } from 'rollup';

export function forceArray<T>(item: T | T[]): T[] {
  if (Array.isArray(item)) {
    return item;
  } else {
    return [item];
  }
}


export function handleMaybeArray<T, R>(
  items: T | T[],
  callback: (item: T) => R
): T extends any[] ? R[] : R {
  if (Array.isArray(items)) {
    return items.map(callback) as any;
  } else {
    return callback(items as T) as any;
  }
}

export function appendToMaybeArray<T>(items: MaybeArray<T>, value: T): T[] {
  if (Array.isArray(items)) {
    items.push(value);

    return items;
  } else {
    return [items, value];
  }
}
