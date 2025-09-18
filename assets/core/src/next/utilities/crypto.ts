import { randomBytes } from 'node:crypto';

export function uniqId(prefix: string = '', size = 16): string {
  let id = randomBytes(size).toString('hex');

  if (prefix) {
    id = prefix + id;
  }

  return id;
}
