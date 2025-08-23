import { type RollupOptions, rollup, watch } from 'rollup';

export async function buildAll(optionsList: RollupOptions[]) {
  for (const options of optionsList) {
    const bundle = await rollup(options);

    const outputs = Array.isArray(options.output) ? options.output : [options.output];

    for (const out of outputs) {
      await bundle.write(out!);
    }

    await bundle.close();
  }

  console.log("✅ Task completed.");
}
export async function watchAll(optionsList: RollupOptions[]) {
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

