import { LoadedConfigTask } from '@/types/runner';
import Module from 'module';

export interface FusionVitePluginOptions {
  fusionfile?: string | Record<string, any>;
  tasks?: string[] | string;
  cwd?: string;
}
