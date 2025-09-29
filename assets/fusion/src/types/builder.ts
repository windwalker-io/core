import { ViteDevServer } from 'vite';

export type WatchTask = string | {
  file: string,
  moduleFile: string;
  updateType: 'js-update' | 'css-update' | 'full-reload';
  // handler: WatchTaskHandler;
};
export type WatchTaskHandler = (server: ViteDevServer, event: 'add' | 'change', path: string) => void;
