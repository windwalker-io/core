import Crypto from 'crypto';

export function shortHash(bufferOrString: Crypto.BinaryLike, short: number | null = 8): string {
  let hash = Crypto.createHash('sha1')
    .update(bufferOrString)
    .digest('hex');

  if (short && short > 0) {
    hash = hash.substring(0, short);
  }

  return hash;
}


