import { globSync } from 'fs';
import fs from 'fs-extra';
import { resolve } from 'node:path';
import { move } from '../dist';
import { clearDest, urlToDirname, viteBuild } from './test-utils';

const __dirname = urlToDirname(import.meta.url);

describe('Test Move', () => {
  beforeEach(() => {
    clearDest();
  });

  it('Move 1 file', async () => {
    fs.copySync(resolve(__dirname, './src/basic'), resolve(__dirname, './dest/basic'));

    await viteBuild(
      () => {
        return {
          default() {
            return [
              move('./dest/basic/flower.txt', 'moved/'),
            ];
          }
        };
      }
    );

    const file = resolve(__dirname, './dest/moved/flower.txt');
    const fileContent = fs.readFileSync(file, 'utf8');
    expect(fs.existsSync(file)).toBeTruthy();
    expect(fileContent).toContain('FLOWER');

    expect(globSync(resolve(__dirname, './dest/moved/*')).length).toBe(1);
  });

  it('Move wildcard 1 level files', async () => {
    fs.copySync(resolve(__dirname, './src/basic'), resolve(__dirname, './dest/basic'));

    await viteBuild(
      () => {
        return {
          default() {
            return [
              move('./dest/basic/*.txt', 'moved/'),
            ];
          }
        };
      }
    );

    const flower = resolve(__dirname, './dest/moved/flower.txt');
    const flowerContent = fs.readFileSync(flower, 'utf8');
    expect(fs.existsSync(flower)).toBeTruthy();
    expect(flowerContent).toContain('FLOWER');

    const car = resolve(__dirname, './dest/moved/car.txt');
    const carContent = fs.readFileSync(car, 'utf8');
    expect(fs.existsSync(car)).toBeTruthy();
    expect(carContent).toContain('CAR');

    expect(globSync(resolve(__dirname, './dest/moved/*')).length).toBe(2);
  });

  it('Move wildcard deep files', async () => {
    fs.copySync(resolve(__dirname, './src/basic'), resolve(__dirname, './dest/basic'));

    await viteBuild(
      () => {
        return {
          default() {
            return [
              move('./dest/basic/**/*.txt', 'moved/'),
            ];
          }
        };
      }
    );

    const flower = resolve(__dirname, './dest/moved/flower.txt');
    const flowerContent = fs.readFileSync(flower, 'utf8');
    expect(fs.existsSync(flower)).toBeTruthy();
    expect(flowerContent).toContain('FLOWER');

    const car = resolve(__dirname, './dest/moved/car.txt');
    const carContent = fs.readFileSync(car, 'utf8');
    expect(fs.existsSync(car)).toBeTruthy();
    expect(carContent).toContain('CAR');

    expect(globSync(resolve(__dirname, './dest/moved/*.txt')).length).toBe(2);
    expect(globSync(resolve(__dirname, './dest/moved/**/*.txt')).length).toBe(6);

    const toyota = resolve(__dirname, './dest/moved/car/toyota.txt');
    const toyotaContent = fs.readFileSync(toyota, 'utf8');
    expect(fs.existsSync(toyota)).toBeTruthy();
    expect(toyotaContent).toContain('TOYOTA');
  });
});
