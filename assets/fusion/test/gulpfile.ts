
import { series, task } from 'gulp';
import fusion from '../src/index.ts';

export async function cssTest() {
  return fusion.css('./src/css/**/*.css', './dest/css/moved/');
}

export default series(cssTest);
