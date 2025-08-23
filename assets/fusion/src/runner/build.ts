import { type RollupOptions, rollup } from 'rollup';

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

