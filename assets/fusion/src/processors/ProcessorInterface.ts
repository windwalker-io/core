import ConfigBuilder from '@/ConfigBuilder.ts';
import { MaybePromise } from 'rollup';
import { UserConfig } from 'vite';

export type ProcessorPreview = {
  input: string;
  output: string;
  extra?: Record<string, any>;
};

export interface ProcessorInterface {
  config(taskName: string, builder: ConfigBuilder): MaybePromise<void>;

  preview(): MaybePromise<ProcessorPreview[]>;
}
