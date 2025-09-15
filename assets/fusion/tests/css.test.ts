import { readFileSync } from 'fs';
import { execSync } from 'node:child_process';
import { existsSync, globSync } from 'node:fs';
import { resolve } from 'node:path';
import { css } from '../dist';
import { clearDest, createViteConfig, importFusionfile, urlToDirname, viteBuild } from './test-utils';

const __dirname = urlToDirname(import.meta.url);

describe('Test CSS from fusionfile', () => {
  beforeEach(() => {
    clearDest();
  });

  it('should work with fusionfile', async () => {
    // execSync('yarn vite build ./tests --mode development -- cssTest');
    await viteBuild({ fusionfile: await importFusionfile(), tasks: 'cssTest' });

    // CSS
    const file1 = resolve(__dirname, './dest/css/foo123.css');
    const file1Content = readFileSync(file1, 'utf8');
    expect(existsSync(file1)).toBeTruthy();
    expect(file1Content).toContain('color: red;');
    expect(file1Content).toContain('::placeholder');

    // SCSS
    const file2 = resolve(__dirname, './dest/css/foosass.css');
    const file2Content = readFileSync(file2, 'utf8');
    expect(existsSync(file2)).toBeTruthy();
    expect(file2Content).toContain('.foo__bar');
    expect(file2Content).toContain('background-color: #fff;');

    // The dest path which not related to outDir should be auto moved.
    expect(globSync(resolve(__dirname, './dest/cssTest-*.css')).length).toBe(0);
  });

  it('should work with fusionfile with move must override', async () => {
    await viteBuild({ fusionfile: await importFusionfile(), tasks: 'cssTest' });
    await viteBuild({ fusionfile: await importFusionfile(), tasks: 'cssTest' });
  });

  it('should copy to dir and keep name', async () => {
    await viteBuild({
      fusionfile: {
        default() {
          return css('src/css/foo.css', 'css/')
        }
      },
    });

    const file1 = resolve(__dirname, './dest/css/foo.css');
    const file1Content = readFileSync(file1, 'utf8');
    expect(existsSync(file1)).toBeTruthy();
    expect(file1Content).toContain('color: red;');
  });
});
