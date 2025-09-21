export * from '@/dep';
import * as fusion from '@/dep';
import ConfigBuilder from '@/builder/ConfigBuilder.ts';
import { prepareParams } from '@/params';
import { getArgsAfterDoubleDashes, parseArgv } from '@/runner/app';
import { expandModules, loadConfigFile, mustGetAvailableConfigFile } from '@/runner/config';
import { displayAvailableTasks } from '@/runner/describe.ts';
import { resolveAllTasksAsProcessors, selectRunningTasks } from '@/runner/tasks.ts';
import { FusionPlugin, FusionVitePluginOptions, FusionVitePluginUnresolved, LoadedConfigTask } from '@/types';
import { forceArray } from '@/utilities/arr.ts';
import { cleanFiles, copyFilesAndLog, linkFilesAndLog, moveFilesAndLog } from '@/utilities/fs.ts';
import { mergeOptions, show } from '@/utilities/utilities.ts';
import fs from 'fs';
import { uniq } from 'lodash-es';
import { existsSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { Logger, mergeConfig, PluginOption, ResolvedConfig, UserConfig } from 'vite';

let params = parseArgv(getArgsAfterDoubleDashes(process.argv));
prepareParams(params);

export let builder: ConfigBuilder;

const originalTasks = params._;
const extraVitePlugins: FusionPlugin[] = [];

export function useFusion(fusionOptions: FusionVitePluginUnresolved = {}, tasks?: string | string[]): PluginOption {
  let logger: Logger;
  let resolvedConfig: ResolvedConfig;
  let exitHandlersBound = false;

  const resolvedOptions = prepareFusionOptions(fusionOptions);

  if (
    typeof tasks === 'string'
    || (Array.isArray(tasks) && tasks.length > 0)
  ) {
    params._ = forceArray(tasks);
  } else {
    params._ = originalTasks;
  }

  params = mergeOptions(params, resolvedOptions.cliParams);

  return [
    {
      name: 'fusion',
      configResolved(config) {
        resolvedConfig = config;

        logger = config.logger;

        // @ts-ignore
        config.plugins.push(...extraVitePlugins);

        for (const plugin of (config.plugins as FusionPlugin[])) {
          if ('buildConfig' in plugin) {
            plugin.buildConfig?.(builder);
          }
        }
      },
      async config(config, env) {
        let root: string;

        if (config.root) {
          root = resolve(config.root);
        } else {
          root = params.cwd || process.cwd();
        }

        delete config.root;
        // delete builder.config.root;

        process.chdir(root);

        builder = new ConfigBuilder(config, env, resolvedOptions);

        // Retrieve config file
        let tasks: Record<string, LoadedConfigTask>;

        if (typeof resolvedOptions.fusionfile === 'string' || !resolvedOptions.fusionfile) {
          params.config ??= resolvedOptions.fusionfile;
          const configFile = mustGetAvailableConfigFile(root, params);

          // Load config
          tasks = await loadConfigFile(configFile);
        } else if (typeof resolvedOptions.fusionfile === 'function') {
          tasks = expandModules(await resolvedOptions.fusionfile());
        } else {
          tasks = expandModules(resolvedOptions.fusionfile);
        }

        // Describe tasks
        if (params.list) {
          await displayAvailableTasks(tasks);
          return;
        }

        // Select running tasks
        const selectedTasks = selectRunningTasks([...params._] as string[], tasks);

        const runningTasks = (await resolveAllTasksAsProcessors(selectedTasks));

        for (const taskName in runningTasks) {
          const processors = runningTasks[taskName];

          for (const processor of processors) {
            await processor.config(taskName, builder);
          }
        }

        builder.merge(ConfigBuilder.globalOverrideConfig);
        builder.merge(builder.overrideConfig);

        // for (const plugin of plugins) {
        //   if (plugin.buildConfig) {
        //     await plugin.buildConfig(builder, env);
        //   }
        // }

        // console.log('plugin bottom', builder.config);
        //
        // show(builder.overrideConfig, 15)
        // show(builder.config, 15)

        return builder.config;
      },
      outputOptions(options) {
        // Protect upload folder
        if (resolvedConfig.build.emptyOutDir) {
          const dir = resolvedConfig.build.outDir;
          const uploadDir = resolve(dir, 'upload');

          if (existsSync(uploadDir)) {
            throw new Error(
              `The output directory: "${dir}" contains an "upload" folder, please move this folder away or set an different fusion outDir.`
            );
          }
        }
      },
      async buildStart(options) {
        if (builder.cleans.length > 0) {
          await cleanFiles(builder.cleans, resolvedConfig.build.outDir || process.cwd());
        }
      },

      // Server
      configureServer(server) {
        server.httpServer?.once('listening', () => {
          const scheme = server.config.server.https ? 'https' : 'http';
          const address = server.httpServer?.address();
          const host = address && typeof address !== 'string' ? address.address : 'localhost';
          const port = address && typeof address !== 'string' ? address.port : 80;

          const url = `${scheme}://${host}:${port}/`;
          const serverFile = resolve(
            server.config.root,
            resolvedOptions.cliParams?.serverFile ?? 'tmp/vite-server'
          );

          writeFileSync(resolve(server.config.root, serverFile), url);

          if (!exitHandlersBound) {
            process.on("exit", () => {
              if (fs.existsSync(serverFile)) {
                fs.rmSync(serverFile);
              }
            });
            process.on("SIGINT", () => process.exit());
            process.on("SIGTERM", () => process.exit());
            process.on("SIGHUP", () => process.exit());
            exitHandlersBound = true;
          }
        });
      }
    },
    {
      name: 'fusion:pre-handles',
      enforce: 'pre',
      async resolveId(source, importer, options) {
        for (const resolveId of builder.resolveIdCallbacks) {
          if (typeof resolveId !== 'function') {
            continue;
          }

          const result = await resolveId.call(this, source, importer, options);

          if (result) {
            return result;
          }
        }

        if (source.startsWith('hidden:')) {
          return source;
        }
      },
      async load(source, options) {
        for (const load of builder.loadCallbacks) {
          if (typeof load !== 'function') {
            continue;
          }

          const result = await load.call(this, source, options);

          if (result) {
            return result;
          }
        }

        if (source.startsWith('hidden:')) {
          return '';
        }
      },
    },
    {
      name: 'fusion:post-handles',
      generateBundle(options, bundle) {
        for (const [fileName, chunk] of Object.entries(bundle)) {
          if (chunk.type === 'chunk' && chunk.facadeModuleId?.startsWith('hidden:')) {
            delete bundle[fileName];
          }
        }
      },
      async writeBundle(options, bundle) {
        const outDir = resolvedConfig.build.outDir || process.cwd();

        // Todo: override logger to replace vite's files logs
        // @see https://github.com/windwalker-io/core/issues/1355
        await moveFilesAndLog(builder.moveTasks, outDir, logger);
        await copyFilesAndLog(builder.copyTasks, outDir, logger);
        await linkFilesAndLog(builder.linkTasks, outDir, logger);

        for (const callback of builder.postBuildCallbacks) {
          await callback();
        }

        for (const [name, task] of builder.tasks) {
          for (const callback of task.postCallbacks) {
            await callback();
          }
        }
      },
    },
  ];
}

function prepareFusionOptions(options: FusionVitePluginUnresolved): FusionVitePluginOptions {
  if (typeof options === 'string') {
    return {
      fusionfile: options,
    };
  }

  if (typeof options === 'function') {
    return {
      fusionfile: options,
    };
  }

  return options;
}

export function configureBuilder(handler: (builder: ConfigBuilder) => void) {
  handler(builder);
}

export function mergeViteConfig(config: UserConfig | null) {
  // if (config === null) {
  //   ConfigBuilder.globalOverrideConfig = {};
  //   return;
  // }
  //
  // ConfigBuilder.globalOverrideConfig = mergeConfig(ConfigBuilder.globalOverrideConfig, config);
  if (config === null) {
    builder.overrideConfig = {};
    return;
  }

  builder.overrideConfig = mergeConfig(builder.overrideConfig, config);
}

export function outDir(outDir: string) {
  // ConfigBuilder.globalOverrideConfig = mergeConfig<UserConfig, UserConfig>(ConfigBuilder.globalOverrideConfig, {
  //   build: {
  //     outDir
  //   }
  // });
  builder.overrideConfig = mergeConfig<UserConfig, UserConfig>(builder.overrideConfig, {
    build: {
      outDir
    }
  });
}

export function chunkDir(dir: string) {
  builder.fusionOptions.chunkDir = dir;
}

export function alias(src: string, dest: string) {
  builder.overrideConfig = mergeConfig<UserConfig, UserConfig>(builder.overrideConfig, {
    resolve: {
      alias: {
        [src]: dest
      }
    }
  });
}

export function external(match: string, varName?: string) {
  const globals: Record<string, string> = {};

  if (varName) {
    globals[match] = varName;
  }

  builder.overrideConfig = mergeConfig<UserConfig, UserConfig>(builder.overrideConfig, {
    build: {
      rollupOptions: {
        external: [match],
        output: {
          globals
        }
      }
    }
  });
}

export function plugin(...plugins: FusionPlugin[]) {
  extraVitePlugins.push(...plugins);
}

export function clean(...paths: string[]) {
  builder.addCleans(...paths);

  builder.cleans = uniq(builder.cleans);
}

export default {
  ...fusion,
  useFusion,
  configureBuilder,
  mergeViteConfig,
  outDir,
  chunkDir,
  alias,
  external,
  plugin,
  clean,
  params,
};
