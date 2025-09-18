import { RunnerCliParams } from '@/types/runner';
import yargs from 'yargs';

export function getArgsAfterDoubleDashes(argv?: string[]): string[] {
  argv ??= process.argv;

  return argv.slice(2).join(' ')
    // Split by -- and remove the first part
    .split(' -- ').slice(1)
    // Join back and split by space
    .join(' -- ').trim()
    // Split back to array and remove empty values
    .split(' ').filter(v => v !== '');
}

export function parseArgv(argv: string[]): RunnerCliParams {
  const app = yargs();

  // app.option('watch', {
  //   alias: 'w',
  //   type: 'boolean',
  //   description: 'Watch files for changes and re-run the tasks',
  // });

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

  app.option('server-file', {
    alias: 's',
    type: 'string',
    description: 'Path to server file',
  });

  // app.option('series', {
  //   alias: 's',
  //   type: 'boolean',
  //   description: 'Run tasks in series instead of parallel',
  // });

  app.option('verbose', {
    alias: 'v',
    type: 'count',
    description: 'Increase verbosity of output. Use multiple times for more verbosity.',
  });

  return app.parseSync(argv);
}
