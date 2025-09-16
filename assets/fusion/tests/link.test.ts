import { globSync, readFileSync } from 'fs';
import fg from 'fast-glob';
import { resolve } from 'node:path';
import { link } from '../dist';
import { clearDest, urlToDirname, viteBuild } from './test-utils';

const __dirname = urlToDirname(import.meta.url);

describe('Test Link', () => {
  beforeEach(() => {
    clearDest();
  });

  it('Link 1 dir', async () => {
    await viteBuild(
      () => {
        return {
          default() {
            return [
              link('./src/basic/flower', 'linked/'),
            ];
          }
        };
      }
    );

    expect(globSync(resolve(__dirname, './dest/linked/flower/*.txt')).length).toBe(2);
    expect(readFileSync(resolve(__dirname, './dest/linked/flower/sakura.txt'), 'utf8')).toContain('SAKURA');
    expect(readFileSync(resolve(__dirname, './dest/linked/flower/rose.txt'), 'utf8')).toContain('ROSE');
  });

  it('Link multiple items', async () => {
    await viteBuild(
      () => {
        return {
          default() {
            return [
              link('./src/basic/*', 'linked/', { force: true }),
            ];
          }
        };
      }
    );

    const files = fg.globSync(fg.convertPathToPattern(resolve(__dirname, './dest/linked/**/*.txt')), { followSymbolicLinks: true });

    expect(files.length).toBe(6);
    expect(readFileSync(resolve(__dirname, './dest/linked/flower.txt'), 'utf8')).toContain('FLOWER');
    expect(readFileSync(resolve(__dirname, './dest/linked/flower/sakura.txt'), 'utf8')).toContain('SAKURA');
    expect(readFileSync(resolve(__dirname, './dest/linked/flower/rose.txt'), 'utf8')).toContain('ROSE');
  });
});
