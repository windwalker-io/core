export * from '@/dep';
import * as fusion from '@/dep';
export default fusion;
//
// const isCliRunning = process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1];
//
// if (isCliRunning) {
//   const params = prepareParams(parseArgv());
//
//   runApp(params!);
// }
export function useFusion(options = {}) {
    return {
        name: 'fusion',
        config(config, env) {
            console.log(env);
        }
    };
}
//# sourceMappingURL=index.js.map