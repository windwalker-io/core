import { RunningTasks } from '@/types';
import chalk from 'chalk';
import { type rollup, watch } from 'rollup';
import { build, defineConfig, type UserConfig, type UserConfigExport } from 'vite';

export async function buildAll(runningTasks: RunningTasks) {
  for (const name in runningTasks) {
    const configList = runningTasks[name];

    console.log(`▶️ - ${chalk.cyan(name)} Start...`);

    for (const config of configList) {
      const output = await build(defineConfig(config));
    }

    console.log(`✅ - ${chalk.cyan(name)} completed.`);
  }
}
export async function watchAll(optionsList: UserConfig[]) {
  const watcher = watch(
    optionsList.map((options) => ({
      ...options,
      watch: {
        // clearScreen: true,
        // buildDelay: 50
      }
    }))
  );

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

  process.on("SIGINT", async () => {
    await watcher.close();
    console.log("\n🛑 STOP Watching...");
    process.exit(0);
  });
}

