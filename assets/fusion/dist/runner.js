import { isAbsolute, resolve } from 'node:path';
import yargs from 'yargs';
import { hideBin } from 'yargs/helpers';
import { rollup, defineConfig } from 'rollup';
import { existsSync } from 'node:fs';
import chalk from 'chalk';
import archy from 'archy';
import { uniq } from 'lodash-es';

async function buildAll(optionsList) {
    for (const options of optionsList) {
        const bundle = await rollup(options);
        const outputs = Array.isArray(options.output) ? options.output : [options.output];
        for (const out of outputs) {
            await bundle.write(out);
        }
        await bundle.close();
    }
    console.log("✅ Task completed.");
}

async function loadConfigFile(configFile) {
    const modules = await import(configFile.path);
    return { ...modules };
}
async function resolveTaskOptions(task, flat = false) {
    if (!flat && Array.isArray(task)) {
        const results = await Promise.all(task.map((task) => resolveTaskOptions(task, true)));
        return results.flat();
    }
    if (typeof task === 'function') {
        return await task();
    }
    return (await task);
}
function mustGetAvailableConfigFile(root, params) {
    const found = getAvailableConfigFile(root, params);
    if (!found) {
        throw new Error('No config file found. Please create a fusionfile.js or fusionfile.ts in the root directory.');
    }
    return found;
}
function getAvailableConfigFile(root, params) {
    let found = params?.config;
    if (found) {
        // If is not absolute of system path(consider Windows), prepend root
        if (!isAbsolute(found)) {
            found = resolve(root, found);
        }
        if (existsSync(found)) {
            return {
                path: found,
                // get filename from file path
                filename: found.split('/').pop() || '',
                type: getConfigModuleType(found),
                ts: isConfigTypeScript(found),
            };
        }
        return null;
    }
    return findDefaultConfig(root);
}
function findDefaultConfig(root) {
    let file = resolve(root, 'fusionfile.js');
    if (existsSync(file)) {
        return {
            path: file,
            // get filename from file path
            filename: file.split('/').pop() || '',
            type: 'commonjs',
            ts: false,
        };
    }
    file = resolve(root, 'fusionfile.mjs');
    if (existsSync(file)) {
        return {
            path: file,
            // get filename from file path
            filename: file.split('/').pop() || '',
            type: 'module',
            ts: false,
        };
    }
    file = resolve(root, 'fusionfile.ts');
    if (existsSync(file)) {
        return {
            path: file,
            // get filename from file path
            filename: file.split('/').pop() || '',
            type: 'module',
            ts: true,
        };
    }
    file = resolve(root, 'fusionfile.mts');
    if (existsSync(file)) {
        return {
            path: file,
            // get filename from file path
            filename: file.split('/').pop() || '',
            type: 'module',
            ts: true,
        };
    }
    return null;
}
function getConfigModuleType(file) {
    let type = 'unknown';
    if (file.endsWith('.cjs')) {
        type = 'commonjs';
    }
    else if (file.endsWith('.mjs')) {
        type = 'module';
    }
    else if (file.endsWith('.ts') || file.endsWith('.mts')) {
        type = 'module';
    }
    return type;
}
function isConfigTypeScript(file) {
    return file.endsWith('.ts') || file.endsWith('.mts');
}

async function displayAvailableTasks(tasks) {
    const keys = Object.keys(tasks);
    // Sort put default as first if exists
    keys.sort((a, b) => {
        if (a === 'default') {
            return -1;
        }
        if (b === 'default') {
            return 1;
        }
        return a.localeCompare(b);
    });
    const nodes = [];
    for (const key of keys) {
        const task = tasks[key];
        const taskOptions = await resolveTaskOptions(task, true);
        nodes.push(await describeTasks(key, taskOptions));
    }
    const text = archy({
        label: chalk.magenta('Available Tasks'),
        nodes
    });
    console.log(text);
}
async function describeTasks(name, tasks) {
    const nodes = [];
    // console.log(name, tasks)
    if (!Array.isArray(tasks)) {
        tasks = [tasks];
    }
    for (const task of tasks) {
        if (typeof task === 'function') {
            const taskOptions = await resolveTaskOptions(task, true);
            nodes.push(await describeTasks(task.name, taskOptions));
        }
        else {
            nodes.push(describeTaskDetail(task));
        }
    }
    return {
        label: chalk.cyan(name),
        nodes
    };
}
function describeTaskDetail(task, indent = 4) {
    const str = [];
    // Input
    if (task.input) {
        let inputStr = '';
        if (typeof task.input === 'string') {
            inputStr = chalk.yellow(task.input);
        }
        else if (Array.isArray(task.input)) {
            inputStr = chalk.yellow(task.input.join(', '));
        }
        else if (typeof task.input === 'object') {
            inputStr = chalk.yellow(Object.values(task.input).join(', '));
        }
        str.push(`Input: ${inputStr}`);
    }
    // Output
    if (task.output) {
        const outputs = Array.isArray(task.output) ? task.output : [task.output];
        outputs.forEach((output, index) => {
            let outStr = '';
            if (output.file) {
                outStr = chalk.green(output.file);
            }
            else if (output.dir) {
                outStr = chalk.green(output.dir);
            }
            str.push(`Output[${index}]: ${outStr}`);
        });
    }
    return str.join(" - ");
}

function selectRunningTasks(input, tasks) {
    input = uniq(input);
    if (input.length === 0) {
        input.push('default');
    }
    const selected = {};
    for (const name of input) {
        if (tasks[name]) {
            selected[name] = tasks[name];
        }
        else {
            throw new Error(`Task "${chalk.cyan(name)}" not found in fusion config.`);
        }
    }
    return selected;
}
async function resolveAllTasksAsOptions(tasks) {
    const cache = {};
    const optionSet = [];
    for (const name in tasks) {
        const task = tasks[name];
        optionSet.push(...await resolveTaskAsFlat(name, task, cache));
    }
    return optionSet;
}
async function resolveTaskAsFlat(name, task, cache) {
    const results = [];
    if (Array.isArray(task)) {
        for (const n in task) {
            const t = task[n];
            results.push(...await resolveTaskAsFlat(n, t, cache));
        }
    }
    else if (typeof task === 'function') {
        name = task.name || name;
        if (cache[name]) {
            return [];
        }
        cache[name] = task;
        const resolved = await resolveTaskOptions(task, true);
        if (Array.isArray(resolved)) {
            for (const n in resolved) {
                const t = resolved[n];
                results.push(...await resolveTaskAsFlat(n, t, cache));
            }
        }
    }
    else {
        results.push(task);
    }
    return results;
}

main();
async function main() {
    const app = yargs();
    app.option('watch', {
        alias: 'w',
        type: 'boolean',
        description: 'Watch files for changes and re-run the tasks',
    });
    app.option('cwd', {
        type: 'string',
        description: 'Current working directory',
    });
    app.option('list', {
        alias: 'l',
        type: 'boolean',
        description: 'List all available tasks',
    });
    app.option('config', {
        alias: 'c',
        type: 'string',
        description: 'Path to config file',
    });
    app.option('verbose', {
        alias: 'v',
        type: 'count',
        description: 'Increase verbosity of output. Use multiple times for more verbosity.',
    });
    const argv = app.parseSync(hideBin(process.argv));
    try {
        await run(argv);
        // Success exit
        process.exit(0);
    }
    catch (e) {
        if (e instanceof Error) {
            if (argv.verbose && argv.verbose > 0) {
                throw e;
            }
            else {
                console.error(e);
                process.exit(1);
            }
        }
        else {
            throw e;
        }
    }
}
async function run(params) {
    let cwd = params?.cwd;
    let root;
    if (cwd) {
        root = cwd = resolve(cwd);
        process.chdir(cwd);
    }
    else {
        root = process.cwd();
    }
    // Retrieve config file
    const configFile = mustGetAvailableConfigFile(root, params);
    // Load config
    const tasks = await loadConfigFile(configFile);
    // Describe tasks
    if (params.list) {
        await displayAvailableTasks(tasks);
        return;
    }
    // Select running tasks
    const selectedTasks = selectRunningTasks([...params._], tasks);
    const options = (await resolveAllTasksAsOptions(selectedTasks));
    await buildAll(defineConfig(options));
}
//# sourceMappingURL=runner.js.map
