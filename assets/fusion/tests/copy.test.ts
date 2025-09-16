import { globSync } from 'fs';
import fs from 'fs-extra';
import { resolve } from 'node:path';
import { copy } from '../dist';
import { clearDest, urlToDirname, viteBuild } from './test-utils';

const __dirname = urlToDirname(import.meta.url);

describe('Test Copy', () => {
  beforeEach(() => {
    clearDest();
  });

  it('Copy 1 file', async () => {
    await viteBuild(
      () => {
        return {
          default() {
            return [
              copy('./src/basic/flower.txt', ''),
            ];
          }
        };
      }
    );

    const file = resolve(__dirname, './dest/flower.txt');
    const fileContent = fs.readFileSync(file, 'utf8');
    expect(fs.existsSync(file)).toBeTruthy();
    expect(fileContent).toContain('FLOWER');

    expect(globSync(resolve(__dirname, './dest/*.txt')).length).toBe(1);
  });

  it('Copy wildcard 1 level files', async () => {
    await viteBuild(
      () => {
        return {
          default() {
            return [
              copy('./src/basic/*.txt', ''),
            ];
          }
        };
      }
    );

    const flower = resolve(__dirname, './dest/flower.txt');
    const flowerContent = fs.readFileSync(flower, 'utf8');
    expect(fs.existsSync(flower)).toBeTruthy();
    expect(flowerContent).toContain('FLOWER');

    const car = resolve(__dirname, './dest/car.txt');
    const carContent = fs.readFileSync(car, 'utf8');
    expect(fs.existsSync(car)).toBeTruthy();
    expect(carContent).toContain('CAR');

    expect(globSync(resolve(__dirname, './dest/*.txt')).length).toBe(2);
  });

  it('Copy wildcard deep files', async () => {
    await viteBuild(
      () => {
        return {
          default() {
            return [
              copy('./src/basic/**/*.txt', ''),
            ];
          }
        };
      }
    );

    const flower = resolve(__dirname, './dest/flower.txt');
    const flowerContent = fs.readFileSync(flower, 'utf8');
    expect(fs.existsSync(flower)).toBeTruthy();
    expect(flowerContent).toContain('FLOWER');

    const car = resolve(__dirname, './dest/car.txt');
    const carContent = fs.readFileSync(car, 'utf8');
    expect(fs.existsSync(car)).toBeTruthy();
    expect(carContent).toContain('CAR');

    expect(globSync(resolve(__dirname, './dest/*.txt')).length).toBe(2);
    expect(globSync(resolve(__dirname, './dest/**/*.txt')).length).toBe(6);

    const toyota = resolve(__dirname, './dest/car/toyota.txt');
    const toyotaContent = fs.readFileSync(toyota, 'utf8');
    expect(fs.existsSync(toyota)).toBeTruthy();
    expect(toyotaContent).toContain('TOYOTA');
  });
});
