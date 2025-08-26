import { RunnerCliParams, RunningTasks } from '@/types';
import chalk from 'chalk';
import { type rollup, RollupWatcher, watch } from 'rollup';
import { build, defineConfig, mergeConfig, type UserConfig, type UserConfigExport } from 'vite';

export async function buildAll(runningTasks: RunningTasks, params: RunnerCliParams) {
  const all = [];

  for (const name in runningTasks) {
    const promises = [];
    const configList = runningTasks[name];

    console.log(`▶️ - ${chalk.cyan(name)} Start...`);

    for (const config of configList) {
      const output = build(defineConfig(config));

      if (params.series) {
        await output;
      }

      promises.push(output);
    }

    const allPromise = Promise.all(promises).then(() => {
      console.log(`✅ - ${chalk.cyan(name)} completed.`);
    });

    if (params.series) {
      await allPromise;
    }

    all.push(allPromise);
  }

  await Promise.all(all);
}
export async function watchAll(runningTasks: RunningTasks, params: RunnerCliParams) {
  const all = [];
  const watcherPromises: Promise<RollupWatcher>[] = [];

  for (const name in runningTasks) {
    const promises = [];
    const configList = runningTasks[name];

    console.log(`▶️ - ${chalk.cyan(name)} Start...`);

    for (const config of configList) {
      const watcher = build(
        mergeConfig(
          defineConfig(config),
          {
            build: { watch: {} },
          }
        )
      ) as Promise<RollupWatcher>;

      watcher.then((watcher) => {
        watcher.on("event", (e) => {
          switch (e.code) {
            case "START":
              console.log("→ Start Watching...");
              break;
            case "BUNDLE_START":
              console.log("→ Start Bundling...");
              break;
            case "BUNDLE_END":
              console.log(`✔ Bundled, uses ${e.duration}ms`);
              // Must manually close it.
              e.result?.close();
              break;
            case "END":
              console.log("Watching...");
              break;
            case "ERROR":
              console.error("✖ ERROR: ", e.error);
              break;
          }
        });
      });

      watcherPromises.push(watcher);
    }
  }

  const watchers: RollupWatcher[] = await Promise.all(watcherPromises);

  process.on("SIGINT", async () => {
    for (const watcher of watchers) {
      await watcher.close();
    }
    console.log("\n🛑 STOP Watching...");
    process.exit(0);
  });
}

