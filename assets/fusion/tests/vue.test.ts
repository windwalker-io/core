import vuePlugin from '@vitejs/plugin-vue';
import { readFileSync } from 'fs';
import { existsSync, globSync } from 'node:fs';
import { resolve } from 'node:path';
import * as fusion from '../dist';
import { js } from '../dist';
import { clearDest, urlToDirname, viteBuild } from './test-utils';

const __dirname = urlToDirname(import.meta.url);

describe('Test Vue', () => {
  beforeEach(() => {
    clearDest();
  });

  it('Vue compile', async () => {
    await viteBuild(() => {
      fusion.alias('@', resolve(__dirname, './src/vue'));
      fusion.external('vue');

      return {
        default() {
          return js('./src/vue/main.ts', 'vue.js');
        }
      };
    }, undefined, {
      plugins: [
        vuePlugin()
      ]
    });

    const vueFile = resolve(__dirname, './dest/vue.js');
    const vueFileContent = readFileSync(vueFile, 'utf8');
    expect(existsSync(vueFile)).toBeTruthy();
    expect(vueFileContent).toContain('import "vue";');
    expect(vueFileContent).toMatch(/import \".\/chunks\/main-(\w+).js\";/);

    const chunks = globSync(resolve(__dirname, './dest/chunks/*.js'));
    expect(chunks.length).toBe(2);

    const aboutFile = chunks[0];
    const aboutContent = readFileSync(aboutFile, 'utf8');
    expect(aboutContent).toContain('This is an about page');

    const mainFile = chunks[1];
    const mainContent = readFileSync(mainFile, 'utf8');
    expect(mainContent).toContain('path: "/about",');
  });

  it('Vue multiple files', async () => {
    await viteBuild(() => {
      fusion.alias('@', resolve(__dirname, './src/vue'));
      fusion.external('vue');

      return {
        default() {
          return [
            js('./src/vue/entries/entry-1.ts', 'entry1.js'),
            js('./src/vue/entries/entry-2.ts', 'entry2.js'),
          ];
        }
      };
    }, undefined, {
      plugins: [
        vuePlugin()
      ]
    });

    const entry1File = resolve(__dirname, './dest/entry1.js');
    const entry1FileContent = readFileSync(entry1File, 'utf8');

    const entry2File = resolve(__dirname, './dest/entry2.js');
    const entry2FileContent = readFileSync(entry2File, 'utf8');

    expect(entry2FileContent).toEqual(entry1FileContent);
  });
});
