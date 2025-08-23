import { OptionsCollection, ProcessorOptions, ProcessSource } from '../types/index.d.ts';

export default class AbstractProcessor<T extends keyof OptionsCollection> {
  public source: ProcessSource;
  public options: ProcessSource;

  constructor(source: ProcessSource, options: ProcessorOptions<T>) {
    this.source = source;
    this.options = options;
  }

  process(dest: ProcessSource): Promise<NodeJS.ReadWriteStream> {
    console.log('DFGDFG');
  }
}

