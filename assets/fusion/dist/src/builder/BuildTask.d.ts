import { PreRenderedChunk } from 'rollup';
export default class BuildTask {
    input: string;
    group?: string | undefined;
    id: string;
    output?: string | ((chunkInfo: PreRenderedChunk) => any);
    postCallbacks: (() => void)[];
    constructor(input: string, group?: string | undefined);
    dest(output?: string | ((chunkInfo: PreRenderedChunk) => any)): this;
    addPostCallback(callback: () => void): this;
    normalizeOutput(output: string, ext?: string): string;
    static toFileId(input: string, group?: string): string;
}
