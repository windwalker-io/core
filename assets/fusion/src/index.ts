export * from '@/dep';
import * as fusion from '@/dep';
import { parseArgv, runApp } from '@/runner/app';
import { FusionVitePluginOptions } from '@/types';
import { fileURLToPath } from 'node:url';
import { prepareParams } from '@/params';
import { PluginOption } from 'vite';

export default fusion;
//
// const isCliRunning = process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1];
//
// if (isCliRunning) {
//   const params = prepareParams(parseArgv());
//
//   runApp(params!);
// }

export function useFusion(options: FusionVitePluginOptions = {}): PluginOption {
  return {
    name: 'fusion',
    config(config, env) {
      console.log(env);
    }
  };
}
