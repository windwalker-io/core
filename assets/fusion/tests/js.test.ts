import { readFileSync } from 'fs';
import { existsSync, globSync } from 'node:fs';
import { resolve } from 'node:path';
import * as fusion from '../dist';
import { js } from '../dist';
import { clearDest, urlToDirname, viteBuild } from './test-utils';

const __dirname = urlToDirname(import.meta.url);

describe('Test JS', () => {
  beforeEach(() => {
    clearDest();
  });

  it('JS single without dest', async () => {
    await viteBuild({
      fusionfile: {
        default() {
          return js('./src/js/single.js');
        }
      },
    });

    const fooFile = resolve(__dirname, './dest/single.js');
    const fooFileContent = readFileSync(fooFile, 'utf8');
    expect(existsSync(fooFile)).toBeTruthy();
    expect(fooFileContent).toContain('console.log("Single instance created")');
    expect(fooFileContent).toContain('console.log("Hello from Single instance")');
    expect(fooFileContent).toContain('new Single()');

    const chunks = globSync(resolve(__dirname, './dest/chunks/*.js'));
    expect(chunks.length).toBe(0);
  });

  it('JS chunks with direct name', async () => {
    // execSync('yarn vite build ./tests --mode development -- cssTest');
    await viteBuild({
      fusionfile: {
        default() {
          return js(
            './src/js/foo.js',
            'js/foo-target.js',
          );
        }
      },
    });

    const fooFile = resolve(__dirname, './dest/js/foo-target.js');
    const fooFileContent = readFileSync(fooFile, 'utf8');
    expect(existsSync(fooFile)).toBeTruthy();
    expect(fooFileContent).toContain('_Foo_decorators = [decorator("Hello")];');
    expect(fooFileContent).toContain('Foo = __decorateElement(_init, 0, "Foo", _Foo_decorators, Foo);');
    expect(fooFileContent).toContain('static car = "Tesla";');
    expect(fooFileContent).not.toContain('//# sourceMappingURL=data:application/json;base64,');

    const chunks = globSync(resolve(__dirname, './dest/chunks/bar-*.js'));
    expect(chunks.length).toBe(1);

    const chunkContent = readFileSync(chunks[0], 'utf8');
    expect(chunkContent).toContain('hoo: "World"');
    expect(chunkContent).toContain('console.log(foo);');
  });

  it('JS chunks with relative dir / sourcemap', async () => {
    // execSync('yarn vite build ./tests --mode development -- cssTest');
    await viteBuild({
      fusionfile: {
        default() {
          return js(
            './src/js/foo.js',
            'js/simple/',
          );
        }
      },
    }, undefined, { mode: 'development' });

    const fooFile = resolve(__dirname, './dest/js/simple/foo.js');
    const fooFileContent = readFileSync(fooFile, 'utf8');
    expect(existsSync(fooFile)).toBeTruthy();
    expect(fooFileContent).toContain('_Foo_decorators = [decorator("Hello")];');
    expect(fooFileContent).toContain('Foo = __decorateElement(_init, 0, "Foo", _Foo_decorators, Foo);');
    expect(fooFileContent).toContain('static car = "Tesla";');
    expect(fooFileContent).toContain('//# sourceMappingURL=data:application/json;base64,');

    const chunks = globSync(resolve(__dirname, './dest/chunks/bar-*.js'));
    expect(chunks.length).toBe(1);

    const chunkContent = readFileSync(chunks[0], 'utf8');
    expect(chunkContent).toContain('hoo: "World"');
    expect(chunkContent).toContain('console.log(foo);');
  });

  it('JS Alias', async () => {
    await viteBuild(() => {
      fusion.alias('@', resolve(__dirname, './src'));

      return ({
        default() {
          return js(
            './src/js/alias.js',

            'js/'
          );
        }
      });
    }, undefined, { mode: 'development' });

    const file = resolve(__dirname, './dest/js/alias.js');
    const content = readFileSync(file, 'utf8');

    expect(existsSync(file)).toBeTruthy();
    expect(content).toMatch(/import { bar } from \"..\/chunks\/bar-(\w+).js\";/);
    expect(content).toContain('console.log(bar);');
  });
});
