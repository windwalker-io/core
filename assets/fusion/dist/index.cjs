'use strict';

Object.defineProperties(exports, { __esModule: { value: true }, [Symbol.toStringTag]: { value: 'Module' } });

const Crypto = require('node:crypto');
const fg = require('fast-glob');
const fs = require('fs-extra');
const node_path = require('node:path');
const node_util = require('node:util');
const vite = require('vite');
const yargs = require('yargs');
const require$$4 = require('child_process');
const require$$0 = require('fs');
const require$$0$1 = require('path');
const require$$1 = require('os');
const require$$6 = require('tty');
const require$$5 = require('crypto');
const Module = require('module');
const fs$1 = require('node:fs');
const archy = require('archy');
const chalk = require('chalk');
const require$$0$2 = require('util');

function forceArray(item) {
  if (Array.isArray(item)) {
    return item;
  } else {
    return [item];
  }
}
function handleMaybeArray(items, callback) {
  if (Array.isArray(items)) {
    return items.map(callback);
  } else {
    return callback(items);
  }
}
function handleForceArray(items, callback) {
  items = forceArray(items);
  return items.map(callback);
}

function css(input, output, options = {}) {
  return new CssProcessor(input, output, options);
}
class CssProcessor {
  constructor(input, output, options = {}) {
    this.input = input;
    this.output = output;
    this.options = options;
  }
  config(taskName, builder) {
    return handleForceArray(this.input, (input) => {
      const task = builder.addTask(input, taskName);
      builder.assetFileNamesCallbacks.push((assetInfo) => {
        const name = assetInfo.names[0];
        if (!name) {
          return void 0;
        }
        if (node_path.basename(name, ".css") === task.id) {
          if (!this.output) {
            return node_path.parse(input).name + ".css";
          }
          return task.normalizeOutput(this.output, ".css");
        }
      });
      return task;
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.output || node_path.basename(input),
        extra: {}
      };
    });
  }
}

function js(input, output) {
  return new JsProcessor(input, output);
}
class JsProcessor {
  constructor(input, output) {
    this.input = input;
    this.output = output;
  }
  config(taskName, builder) {
    return handleForceArray(this.input, (input) => {
      const task = builder.addTask(input, taskName);
      builder.entryFileNamesCallbacks.push((chunkInfo) => {
        const name = chunkInfo.name;
        if (!name) {
          return;
        }
        if (name === task.id) {
          if (!this.output) {
            return node_path.parse(input).name + ".js";
          }
          return task.normalizeOutput(this.output);
        }
      });
      return task;
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.output || node_path.basename(input),
        extra: {}
      };
    });
  }
}

function move(input, dest) {
  return new MoveProcessor(input, dest);
}
class MoveProcessor {
  constructor(input, dest) {
    this.input = input;
    this.dest = dest;
  }
  config(taskName, builder) {
    handleMaybeArray(this.input, (input) => {
      builder.moveTasks.push({ src: input, dest: this.dest, options: {} });
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.dest,
        extra: {}
      };
    });
  }
}

function copy(input, dest) {
  return new CopyProcessor(input, dest);
}
class CopyProcessor {
  constructor(input, dest) {
    this.input = input;
    this.dest = dest;
  }
  config(taskName, builder) {
    handleMaybeArray(this.input, (input) => {
      builder.copyTasks.push({ src: input, dest: this.dest, options: {} });
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.dest,
        extra: {}
      };
    });
  }
}

function link(input, dest, options = {}) {
  return new LinkProcessor(input, dest, options);
}
class LinkProcessor {
  constructor(input, dest, options = {}) {
    this.input = input;
    this.dest = dest;
    this.options = options;
  }
  config(taskName, builder) {
    handleMaybeArray(this.input, (input) => {
      builder.linkTasks.push({ src: input, dest: this.dest, options: this.options });
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.dest,
        extra: {}
      };
    });
  }
}

function callback(handler) {
  return new CallbackProcessor(handler);
}
function callbackAfterBuild(handler) {
  return new CallbackProcessor(handler, true);
}
class CallbackProcessor {
  constructor(handler, afterBuild = false) {
    this.handler = handler;
    this.afterBuild = afterBuild;
  }
  config(taskName, builder) {
    if (this.afterBuild) {
      builder.postBuildCallbacks.push(() => this.handler(taskName, builder));
    } else {
      this.handler(taskName, builder);
    }
    return void 0;
  }
  preview() {
    return [];
  }
}

exports.params = void 0;
function prepareParams(p) {
  exports.params = p;
  exports.isVerbose = exports.params?.verbose ? exports.params?.verbose > 0 : false;
  return p;
}
exports.isVerbose = false;
const isProd = process.env.NODE_ENV === "production";
const isDev = !isProd;

function isWindows() {
  return process.platform === "win32";
}

function shortHash(bufferOrString, short = 8) {
  let hash = Crypto.createHash("sha1").update(bufferOrString).digest("hex");
  if (short && short > 0) {
    hash = hash.substring(0, short);
  }
  return hash;
}

function handleFilesOperation(src, dest, options) {
  const promises = [];
  src = normalizeFilePath(src, options.outDir);
  dest = normalizeFilePath(dest, options.outDir);
  const base = getGlobBaseFromPattern(src);
  const sources = isGlob(src) ? fg.globSync(src.replace(/\\/g, "/"), options.globOptions) : [src];
  for (let source of sources) {
    let dir;
    let resolvedDest = dest;
    if (endsWithSlash(dest)) {
      dir = resolvedDest;
      resolvedDest = resolvedDest + node_path.relative(base, source);
    } else {
      dir = node_path.dirname(resolvedDest);
    }
    fs.ensureDirSync(dir);
    promises.push(options.handler(source, resolvedDest));
  }
  return promises;
}
function moveFilesAndLog(tasks, outDir, logger) {
  const promises = [];
  for (const { src, dest, options } of tasks) {
    const ps = handleFilesOperation(
      src,
      dest,
      {
        outDir,
        handler: async (src2, dest2) => {
          logger.info(`Moving file from ${node_path.relative(outDir, src2)} to ${node_path.relative(outDir, dest2)}`);
          return fs.move(src2, dest2, { overwrite: true });
        },
        globOptions: { onlyFiles: true }
      }
    );
    promises.push(...ps);
  }
  return Promise.all(promises);
}
function copyFilesAndLog(tasks, outDir, logger) {
  const promises = [];
  for (const { src, dest, options } of tasks) {
    const ps = handleFilesOperation(
      src,
      dest,
      {
        outDir,
        handler: async (src2, dest2) => {
          logger.info(`Copy file from ${node_path.relative(outDir, src2)} to ${node_path.relative(outDir, dest2)}`);
          return fs.copy(src2, dest2, { overwrite: true });
        },
        globOptions: { onlyFiles: true }
      }
    );
    promises.push(...ps);
  }
  return Promise.all(promises);
}
function linkFilesAndLog(tasks, outDir, logger) {
  const promises = [];
  for (const { src, dest, options } of tasks) {
    const ps = handleFilesOperation(
      src,
      dest,
      {
        outDir,
        handler: async (src2, dest2) => {
          logger.info(`Link file from ${node_path.relative(outDir, src2)} to ${node_path.relative(outDir, dest2)}`);
          return symlink(src2, dest2, options?.force ?? false);
        },
        globOptions: { onlyFiles: false }
      }
    );
    promises.push(...ps);
  }
  return Promise.all(promises);
}
function cleanFiles(patterns, outDir) {
  const promises = [];
  outDir = outDir.replace(/\\/g, "/");
  for (let src of patterns) {
    src = normalizeFilePath(src, outDir);
    src = node_path.resolve(src);
    const sources = isGlob(src) ? fg.globSync(src.replace(/\\/g, "/"), { onlyFiles: false }) : [src];
    const protectDir = node_path.resolve(outDir + "/upload").replace(/\\/g, "/");
    for (let source of sources) {
      if (source.replace(/\\/g, "/").startsWith(protectDir)) {
        throw new Error("Refuse to delete `upload/*` folder.");
      }
      promises.push(fs.remove(source));
    }
  }
  return Promise.all(promises);
}
async function copyGlob(src, dest) {
  const promises = handleFilesOperation(
    src,
    dest,
    {
      outDir: process.cwd(),
      handler: async (src2, dest2) => fs.copy(src2, dest2, { overwrite: true }),
      globOptions: { onlyFiles: true }
    }
  );
  await Promise.all(promises);
}
async function moveGlob(src, dest) {
  const promises = handleFilesOperation(
    src,
    dest,
    {
      outDir: process.cwd(),
      handler: async (src2, dest2) => fs.move(src2, dest2, { overwrite: true }),
      globOptions: { onlyFiles: true }
    }
  );
  await Promise.all(promises);
}
async function symlink(target, link, force = false) {
  target = node_path.resolve(target);
  link = node_path.resolve(link);
  if (isWindows() && !fs.lstatSync(target).isFile()) {
    return fs.ensureSymlink(target, link, "junction");
  }
  if (isWindows() && fs.lstatSync(target).isFile() && force) {
    return fs.ensureLink(target, link);
  }
  return fs.ensureSymlink(target, link);
}
function endsWithSlash(path) {
  return path.endsWith("/") || path.endsWith("\\");
}
function getGlobBaseFromPattern(pattern) {
  const specialChars = ["*", "?", "[", "]"];
  const idx = [...pattern].findIndex((c) => specialChars.includes(c));
  if (idx === -1) {
    return node_path.dirname(pattern);
  }
  return node_path.dirname(pattern.slice(0, idx + 1));
}
function isGlob(pattern) {
  const specialChars = ["*", "?", "[", "]"];
  return specialChars.some((c) => pattern.includes(c));
}
function normalizeFilePath(path, outDir) {
  if (path.startsWith(".")) {
    path = node_path.resolve(path);
  } else if (!node_path.isAbsolute(path)) {
    path = outDir + "/" + path;
  }
  return path;
}
function fileToId(input, group) {
  input = node_path.normalize(input);
  group ||= Crypto.randomBytes(4).toString("hex");
  return group + "-" + shortHash(input);
}

const fusion = /*#__PURE__*/Object.freeze(/*#__PURE__*/Object.defineProperty({
  __proto__: null,
  callback,
  callbackAfterBuild,
  copy,
  copyGlob,
  css,
  fileToId,
  getGlobBaseFromPattern,
  isDev,
  isProd,
  get isVerbose () { return exports.isVerbose; },
  isWindows,
  js,
  link,
  move,
  moveGlob,
  get params () { return exports.params; },
  shortHash,
  symlink
}, Symbol.toStringTag, { value: 'Module' }));

class BuildTask {
  constructor(input, group) {
    this.input = input;
    this.group = group;
    this.id = BuildTask.toFileId(input, group);
    this.input = node_path.normalize(input);
  }
  id;
  output;
  postCallbacks = [];
  dest(output) {
    if (typeof output === "string") {
      output = this.normalizeOutput(output);
    }
    this.output = output;
    return this;
  }
  addPostCallback(callback) {
    this.postCallbacks.push(callback);
    return this;
  }
  normalizeOutput(output, ext = ".js") {
    if (output.endsWith("/") || output.endsWith("\\")) {
      output += node_path.parse(this.input).name + ext;
    }
    return output;
  }
  static toFileId(input, group) {
    return fileToId(input, group);
  }
}

/** Detect free variable `global` from Node.js. */
var freeGlobal = typeof global == 'object' && global && global.Object === Object && global;

/** Detect free variable `self`. */
var freeSelf = typeof self == 'object' && self && self.Object === Object && self;

/** Used as a reference to the global object. */
var root = freeGlobal || freeSelf || Function('return this')();

/** Built-in value references. */
var Symbol$1 = root.Symbol;

/** Used for built-in method references. */
var objectProto$5 = Object.prototype;

/** Used to check objects for own properties. */
var hasOwnProperty$4 = objectProto$5.hasOwnProperty;

/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */
var nativeObjectToString$1 = objectProto$5.toString;

/** Built-in value references. */
var symToStringTag$1 = Symbol$1 ? Symbol$1.toStringTag : undefined;

/**
 * A specialized version of `baseGetTag` which ignores `Symbol.toStringTag` values.
 *
 * @private
 * @param {*} value The value to query.
 * @returns {string} Returns the raw `toStringTag`.
 */
function getRawTag(value) {
  var isOwn = hasOwnProperty$4.call(value, symToStringTag$1),
      tag = value[symToStringTag$1];

  try {
    value[symToStringTag$1] = undefined;
    var unmasked = true;
  } catch (e) {}

  var result = nativeObjectToString$1.call(value);
  if (unmasked) {
    if (isOwn) {
      value[symToStringTag$1] = tag;
    } else {
      delete value[symToStringTag$1];
    }
  }
  return result;
}

/** Used for built-in method references. */
var objectProto$4 = Object.prototype;

/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */
var nativeObjectToString = objectProto$4.toString;

/**
 * Converts `value` to a string using `Object.prototype.toString`.
 *
 * @private
 * @param {*} value The value to convert.
 * @returns {string} Returns the converted string.
 */
function objectToString(value) {
  return nativeObjectToString.call(value);
}

/** `Object#toString` result references. */
var nullTag = '[object Null]',
    undefinedTag = '[object Undefined]';

/** Built-in value references. */
var symToStringTag = Symbol$1 ? Symbol$1.toStringTag : undefined;

/**
 * The base implementation of `getTag` without fallbacks for buggy environments.
 *
 * @private
 * @param {*} value The value to query.
 * @returns {string} Returns the `toStringTag`.
 */
function baseGetTag(value) {
  if (value == null) {
    return value === undefined ? undefinedTag : nullTag;
  }
  return (symToStringTag && symToStringTag in Object(value))
    ? getRawTag(value)
    : objectToString(value);
}

/**
 * Checks if `value` is object-like. A value is object-like if it's not `null`
 * and has a `typeof` result of "object".
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 * @example
 *
 * _.isObjectLike({});
 * // => true
 *
 * _.isObjectLike([1, 2, 3]);
 * // => true
 *
 * _.isObjectLike(_.noop);
 * // => false
 *
 * _.isObjectLike(null);
 * // => false
 */
function isObjectLike(value) {
  return value != null && typeof value == 'object';
}

/** `Object#toString` result references. */
var symbolTag = '[object Symbol]';

/**
 * Checks if `value` is classified as a `Symbol` primitive or object.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a symbol, else `false`.
 * @example
 *
 * _.isSymbol(Symbol.iterator);
 * // => true
 *
 * _.isSymbol('abc');
 * // => false
 */
function isSymbol(value) {
  return typeof value == 'symbol' ||
    (isObjectLike(value) && baseGetTag(value) == symbolTag);
}

/**
 * A specialized version of `_.map` for arrays without support for iteratee
 * shorthands.
 *
 * @private
 * @param {Array} [array] The array to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @returns {Array} Returns the new mapped array.
 */
function arrayMap(array, iteratee) {
  var index = -1,
      length = array == null ? 0 : array.length,
      result = Array(length);

  while (++index < length) {
    result[index] = iteratee(array[index], index, array);
  }
  return result;
}

/**
 * Checks if `value` is classified as an `Array` object.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an array, else `false`.
 * @example
 *
 * _.isArray([1, 2, 3]);
 * // => true
 *
 * _.isArray(document.body.children);
 * // => false
 *
 * _.isArray('abc');
 * // => false
 *
 * _.isArray(_.noop);
 * // => false
 */
var isArray = Array.isArray;

/** Used to convert symbols to primitives and strings. */
var symbolProto = Symbol$1 ? Symbol$1.prototype : undefined,
    symbolToString = symbolProto ? symbolProto.toString : undefined;

/**
 * The base implementation of `_.toString` which doesn't convert nullish
 * values to empty strings.
 *
 * @private
 * @param {*} value The value to process.
 * @returns {string} Returns the string.
 */
function baseToString(value) {
  // Exit early for strings to avoid a performance hit in some environments.
  if (typeof value == 'string') {
    return value;
  }
  if (isArray(value)) {
    // Recursively convert values (susceptible to call stack limits).
    return arrayMap(value, baseToString) + '';
  }
  if (isSymbol(value)) {
    return symbolToString ? symbolToString.call(value) : '';
  }
  var result = (value + '');
  return (result == '0' && (1 / value) == -Infinity) ? '-0' : result;
}

/**
 * Checks if `value` is the
 * [language type](http://www.ecma-international.org/ecma-262/7.0/#sec-ecmascript-language-types)
 * of `Object`. (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(_.noop);
 * // => true
 *
 * _.isObject(null);
 * // => false
 */
function isObject(value) {
  var type = typeof value;
  return value != null && (type == 'object' || type == 'function');
}

/** `Object#toString` result references. */
var asyncTag = '[object AsyncFunction]',
    funcTag = '[object Function]',
    genTag = '[object GeneratorFunction]',
    proxyTag = '[object Proxy]';

/**
 * Checks if `value` is classified as a `Function` object.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a function, else `false`.
 * @example
 *
 * _.isFunction(_);
 * // => true
 *
 * _.isFunction(/abc/);
 * // => false
 */
function isFunction(value) {
  if (!isObject(value)) {
    return false;
  }
  // The use of `Object#toString` avoids issues with the `typeof` operator
  // in Safari 9 which returns 'object' for typed arrays and other constructors.
  var tag = baseGetTag(value);
  return tag == funcTag || tag == genTag || tag == asyncTag || tag == proxyTag;
}

/** Used to detect overreaching core-js shims. */
var coreJsData = root['__core-js_shared__'];

/** Used to detect methods masquerading as native. */
var maskSrcKey = (function() {
  var uid = /[^.]+$/.exec(coreJsData && coreJsData.keys && coreJsData.keys.IE_PROTO || '');
  return uid ? ('Symbol(src)_1.' + uid) : '';
}());

/**
 * Checks if `func` has its source masked.
 *
 * @private
 * @param {Function} func The function to check.
 * @returns {boolean} Returns `true` if `func` is masked, else `false`.
 */
function isMasked(func) {
  return !!maskSrcKey && (maskSrcKey in func);
}

/** Used for built-in method references. */
var funcProto$1 = Function.prototype;

/** Used to resolve the decompiled source of functions. */
var funcToString$1 = funcProto$1.toString;

/**
 * Converts `func` to its source code.
 *
 * @private
 * @param {Function} func The function to convert.
 * @returns {string} Returns the source code.
 */
function toSource(func) {
  if (func != null) {
    try {
      return funcToString$1.call(func);
    } catch (e) {}
    try {
      return (func + '');
    } catch (e) {}
  }
  return '';
}

/**
 * Used to match `RegExp`
 * [syntax characters](http://ecma-international.org/ecma-262/7.0/#sec-patterns).
 */
var reRegExpChar = /[\\^$.*+?()[\]{}|]/g;

/** Used to detect host constructors (Safari). */
var reIsHostCtor = /^\[object .+?Constructor\]$/;

/** Used for built-in method references. */
var funcProto = Function.prototype,
    objectProto$3 = Object.prototype;

/** Used to resolve the decompiled source of functions. */
var funcToString = funcProto.toString;

/** Used to check objects for own properties. */
var hasOwnProperty$3 = objectProto$3.hasOwnProperty;

/** Used to detect if a method is native. */
var reIsNative = RegExp('^' +
  funcToString.call(hasOwnProperty$3).replace(reRegExpChar, '\\$&')
  .replace(/hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g, '$1.*?') + '$'
);

/**
 * The base implementation of `_.isNative` without bad shim checks.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a native function,
 *  else `false`.
 */
function baseIsNative(value) {
  if (!isObject(value) || isMasked(value)) {
    return false;
  }
  var pattern = isFunction(value) ? reIsNative : reIsHostCtor;
  return pattern.test(toSource(value));
}

/**
 * Gets the value at `key` of `object`.
 *
 * @private
 * @param {Object} [object] The object to query.
 * @param {string} key The key of the property to get.
 * @returns {*} Returns the property value.
 */
function getValue(object, key) {
  return object == null ? undefined : object[key];
}

/**
 * Gets the native function at `key` of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @param {string} key The key of the method to get.
 * @returns {*} Returns the function if it's native, else `undefined`.
 */
function getNative(object, key) {
  var value = getValue(object, key);
  return baseIsNative(value) ? value : undefined;
}

/**
 * This method returns `undefined`.
 *
 * @static
 * @memberOf _
 * @since 2.3.0
 * @category Util
 * @example
 *
 * _.times(2, _.noop);
 * // => [undefined, undefined]
 */
function noop() {
  // No operation performed.
}

var defineProperty = (function() {
  try {
    var func = getNative(Object, 'defineProperty');
    func({}, '', {});
    return func;
  } catch (e) {}
}());

/**
 * The base implementation of `_.findIndex` and `_.findLastIndex` without
 * support for iteratee shorthands.
 *
 * @private
 * @param {Array} array The array to inspect.
 * @param {Function} predicate The function invoked per iteration.
 * @param {number} fromIndex The index to search from.
 * @param {boolean} [fromRight] Specify iterating from right to left.
 * @returns {number} Returns the index of the matched value, else `-1`.
 */
function baseFindIndex(array, predicate, fromIndex, fromRight) {
  var length = array.length,
      index = fromIndex + (-1);

  while ((++index < length)) {
    if (predicate(array[index], index, array)) {
      return index;
    }
  }
  return -1;
}

/**
 * The base implementation of `_.isNaN` without support for number objects.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is `NaN`, else `false`.
 */
function baseIsNaN(value) {
  return value !== value;
}

/**
 * A specialized version of `_.indexOf` which performs strict equality
 * comparisons of values, i.e. `===`.
 *
 * @private
 * @param {Array} array The array to inspect.
 * @param {*} value The value to search for.
 * @param {number} fromIndex The index to search from.
 * @returns {number} Returns the index of the matched value, else `-1`.
 */
function strictIndexOf(array, value, fromIndex) {
  var index = fromIndex - 1,
      length = array.length;

  while (++index < length) {
    if (array[index] === value) {
      return index;
    }
  }
  return -1;
}

/**
 * The base implementation of `_.indexOf` without `fromIndex` bounds checks.
 *
 * @private
 * @param {Array} array The array to inspect.
 * @param {*} value The value to search for.
 * @param {number} fromIndex The index to search from.
 * @returns {number} Returns the index of the matched value, else `-1`.
 */
function baseIndexOf(array, value, fromIndex) {
  return value === value
    ? strictIndexOf(array, value, fromIndex)
    : baseFindIndex(array, baseIsNaN, fromIndex);
}

/**
 * A specialized version of `_.includes` for arrays without support for
 * specifying an index to search from.
 *
 * @private
 * @param {Array} [array] The array to inspect.
 * @param {*} target The value to search for.
 * @returns {boolean} Returns `true` if `target` is found, else `false`.
 */
function arrayIncludes(array, value) {
  var length = array == null ? 0 : array.length;
  return !!length && baseIndexOf(array, value, 0) > -1;
}

/** Used as references for various `Number` constants. */
var MAX_SAFE_INTEGER = 9007199254740991;

/** Used to detect unsigned integer values. */
var reIsUint = /^(?:0|[1-9]\d*)$/;

/**
 * Checks if `value` is a valid array-like index.
 *
 * @private
 * @param {*} value The value to check.
 * @param {number} [length=MAX_SAFE_INTEGER] The upper bounds of a valid index.
 * @returns {boolean} Returns `true` if `value` is a valid index, else `false`.
 */
function isIndex(value, length) {
  var type = typeof value;
  length = length == null ? MAX_SAFE_INTEGER : length;

  return !!length &&
    (type == 'number' ||
      (type != 'symbol' && reIsUint.test(value))) &&
        (value > -1 && value % 1 == 0 && value < length);
}

/**
 * The base implementation of `assignValue` and `assignMergeValue` without
 * value checks.
 *
 * @private
 * @param {Object} object The object to modify.
 * @param {string} key The key of the property to assign.
 * @param {*} value The value to assign.
 */
function baseAssignValue(object, key, value) {
  if (key == '__proto__' && defineProperty) {
    defineProperty(object, key, {
      'configurable': true,
      'enumerable': true,
      'value': value,
      'writable': true
    });
  } else {
    object[key] = value;
  }
}

/**
 * Performs a
 * [`SameValueZero`](http://ecma-international.org/ecma-262/7.0/#sec-samevaluezero)
 * comparison between two values to determine if they are equivalent.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to compare.
 * @param {*} other The other value to compare.
 * @returns {boolean} Returns `true` if the values are equivalent, else `false`.
 * @example
 *
 * var object = { 'a': 1 };
 * var other = { 'a': 1 };
 *
 * _.eq(object, object);
 * // => true
 *
 * _.eq(object, other);
 * // => false
 *
 * _.eq('a', 'a');
 * // => true
 *
 * _.eq('a', Object('a'));
 * // => false
 *
 * _.eq(NaN, NaN);
 * // => true
 */
function eq(value, other) {
  return value === other || (value !== value && other !== other);
}

/** Used for built-in method references. */
var objectProto$2 = Object.prototype;

/** Used to check objects for own properties. */
var hasOwnProperty$2 = objectProto$2.hasOwnProperty;

/**
 * Assigns `value` to `key` of `object` if the existing value is not equivalent
 * using [`SameValueZero`](http://ecma-international.org/ecma-262/7.0/#sec-samevaluezero)
 * for equality comparisons.
 *
 * @private
 * @param {Object} object The object to modify.
 * @param {string} key The key of the property to assign.
 * @param {*} value The value to assign.
 */
function assignValue(object, key, value) {
  var objValue = object[key];
  if (!(hasOwnProperty$2.call(object, key) && eq(objValue, value)) ||
      (value === undefined && !(key in object))) {
    baseAssignValue(object, key, value);
  }
}

/** Used to match property names within property paths. */
var reIsDeepProp = /\.|\[(?:[^[\]]*|(["'])(?:(?!\1)[^\\]|\\.)*?\1)\]/,
    reIsPlainProp = /^\w*$/;

/**
 * Checks if `value` is a property name and not a property path.
 *
 * @private
 * @param {*} value The value to check.
 * @param {Object} [object] The object to query keys on.
 * @returns {boolean} Returns `true` if `value` is a property name, else `false`.
 */
function isKey(value, object) {
  if (isArray(value)) {
    return false;
  }
  var type = typeof value;
  if (type == 'number' || type == 'symbol' || type == 'boolean' ||
      value == null || isSymbol(value)) {
    return true;
  }
  return reIsPlainProp.test(value) || !reIsDeepProp.test(value) ||
    (object != null && value in Object(object));
}

/* Built-in method references that are verified to be native. */
var nativeCreate = getNative(Object, 'create');

/**
 * Removes all key-value entries from the hash.
 *
 * @private
 * @name clear
 * @memberOf Hash
 */
function hashClear() {
  this.__data__ = nativeCreate ? nativeCreate(null) : {};
  this.size = 0;
}

/**
 * Removes `key` and its value from the hash.
 *
 * @private
 * @name delete
 * @memberOf Hash
 * @param {Object} hash The hash to modify.
 * @param {string} key The key of the value to remove.
 * @returns {boolean} Returns `true` if the entry was removed, else `false`.
 */
function hashDelete(key) {
  var result = this.has(key) && delete this.__data__[key];
  this.size -= result ? 1 : 0;
  return result;
}

/** Used to stand-in for `undefined` hash values. */
var HASH_UNDEFINED$2 = '__lodash_hash_undefined__';

/** Used for built-in method references. */
var objectProto$1 = Object.prototype;

/** Used to check objects for own properties. */
var hasOwnProperty$1 = objectProto$1.hasOwnProperty;

/**
 * Gets the hash value for `key`.
 *
 * @private
 * @name get
 * @memberOf Hash
 * @param {string} key The key of the value to get.
 * @returns {*} Returns the entry value.
 */
function hashGet(key) {
  var data = this.__data__;
  if (nativeCreate) {
    var result = data[key];
    return result === HASH_UNDEFINED$2 ? undefined : result;
  }
  return hasOwnProperty$1.call(data, key) ? data[key] : undefined;
}

/** Used for built-in method references. */
var objectProto = Object.prototype;

/** Used to check objects for own properties. */
var hasOwnProperty = objectProto.hasOwnProperty;

/**
 * Checks if a hash value for `key` exists.
 *
 * @private
 * @name has
 * @memberOf Hash
 * @param {string} key The key of the entry to check.
 * @returns {boolean} Returns `true` if an entry for `key` exists, else `false`.
 */
function hashHas(key) {
  var data = this.__data__;
  return nativeCreate ? (data[key] !== undefined) : hasOwnProperty.call(data, key);
}

/** Used to stand-in for `undefined` hash values. */
var HASH_UNDEFINED$1 = '__lodash_hash_undefined__';

/**
 * Sets the hash `key` to `value`.
 *
 * @private
 * @name set
 * @memberOf Hash
 * @param {string} key The key of the value to set.
 * @param {*} value The value to set.
 * @returns {Object} Returns the hash instance.
 */
function hashSet(key, value) {
  var data = this.__data__;
  this.size += this.has(key) ? 0 : 1;
  data[key] = (nativeCreate && value === undefined) ? HASH_UNDEFINED$1 : value;
  return this;
}

/**
 * Creates a hash object.
 *
 * @private
 * @constructor
 * @param {Array} [entries] The key-value pairs to cache.
 */
function Hash(entries) {
  var index = -1,
      length = entries == null ? 0 : entries.length;

  this.clear();
  while (++index < length) {
    var entry = entries[index];
    this.set(entry[0], entry[1]);
  }
}

// Add methods to `Hash`.
Hash.prototype.clear = hashClear;
Hash.prototype['delete'] = hashDelete;
Hash.prototype.get = hashGet;
Hash.prototype.has = hashHas;
Hash.prototype.set = hashSet;

/**
 * Removes all key-value entries from the list cache.
 *
 * @private
 * @name clear
 * @memberOf ListCache
 */
function listCacheClear() {
  this.__data__ = [];
  this.size = 0;
}

/**
 * Gets the index at which the `key` is found in `array` of key-value pairs.
 *
 * @private
 * @param {Array} array The array to inspect.
 * @param {*} key The key to search for.
 * @returns {number} Returns the index of the matched value, else `-1`.
 */
function assocIndexOf(array, key) {
  var length = array.length;
  while (length--) {
    if (eq(array[length][0], key)) {
      return length;
    }
  }
  return -1;
}

/** Used for built-in method references. */
var arrayProto = Array.prototype;

/** Built-in value references. */
var splice = arrayProto.splice;

/**
 * Removes `key` and its value from the list cache.
 *
 * @private
 * @name delete
 * @memberOf ListCache
 * @param {string} key The key of the value to remove.
 * @returns {boolean} Returns `true` if the entry was removed, else `false`.
 */
function listCacheDelete(key) {
  var data = this.__data__,
      index = assocIndexOf(data, key);

  if (index < 0) {
    return false;
  }
  var lastIndex = data.length - 1;
  if (index == lastIndex) {
    data.pop();
  } else {
    splice.call(data, index, 1);
  }
  --this.size;
  return true;
}

/**
 * Gets the list cache value for `key`.
 *
 * @private
 * @name get
 * @memberOf ListCache
 * @param {string} key The key of the value to get.
 * @returns {*} Returns the entry value.
 */
function listCacheGet(key) {
  var data = this.__data__,
      index = assocIndexOf(data, key);

  return index < 0 ? undefined : data[index][1];
}

/**
 * Checks if a list cache value for `key` exists.
 *
 * @private
 * @name has
 * @memberOf ListCache
 * @param {string} key The key of the entry to check.
 * @returns {boolean} Returns `true` if an entry for `key` exists, else `false`.
 */
function listCacheHas(key) {
  return assocIndexOf(this.__data__, key) > -1;
}

/**
 * Sets the list cache `key` to `value`.
 *
 * @private
 * @name set
 * @memberOf ListCache
 * @param {string} key The key of the value to set.
 * @param {*} value The value to set.
 * @returns {Object} Returns the list cache instance.
 */
function listCacheSet(key, value) {
  var data = this.__data__,
      index = assocIndexOf(data, key);

  if (index < 0) {
    ++this.size;
    data.push([key, value]);
  } else {
    data[index][1] = value;
  }
  return this;
}

/**
 * Creates an list cache object.
 *
 * @private
 * @constructor
 * @param {Array} [entries] The key-value pairs to cache.
 */
function ListCache(entries) {
  var index = -1,
      length = entries == null ? 0 : entries.length;

  this.clear();
  while (++index < length) {
    var entry = entries[index];
    this.set(entry[0], entry[1]);
  }
}

// Add methods to `ListCache`.
ListCache.prototype.clear = listCacheClear;
ListCache.prototype['delete'] = listCacheDelete;
ListCache.prototype.get = listCacheGet;
ListCache.prototype.has = listCacheHas;
ListCache.prototype.set = listCacheSet;

/* Built-in method references that are verified to be native. */
var Map$1 = getNative(root, 'Map');

/**
 * Removes all key-value entries from the map.
 *
 * @private
 * @name clear
 * @memberOf MapCache
 */
function mapCacheClear() {
  this.size = 0;
  this.__data__ = {
    'hash': new Hash,
    'map': new (Map$1 || ListCache),
    'string': new Hash
  };
}

/**
 * Checks if `value` is suitable for use as unique object key.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is suitable, else `false`.
 */
function isKeyable(value) {
  var type = typeof value;
  return (type == 'string' || type == 'number' || type == 'symbol' || type == 'boolean')
    ? (value !== '__proto__')
    : (value === null);
}

/**
 * Gets the data for `map`.
 *
 * @private
 * @param {Object} map The map to query.
 * @param {string} key The reference key.
 * @returns {*} Returns the map data.
 */
function getMapData(map, key) {
  var data = map.__data__;
  return isKeyable(key)
    ? data[typeof key == 'string' ? 'string' : 'hash']
    : data.map;
}

/**
 * Removes `key` and its value from the map.
 *
 * @private
 * @name delete
 * @memberOf MapCache
 * @param {string} key The key of the value to remove.
 * @returns {boolean} Returns `true` if the entry was removed, else `false`.
 */
function mapCacheDelete(key) {
  var result = getMapData(this, key)['delete'](key);
  this.size -= result ? 1 : 0;
  return result;
}

/**
 * Gets the map value for `key`.
 *
 * @private
 * @name get
 * @memberOf MapCache
 * @param {string} key The key of the value to get.
 * @returns {*} Returns the entry value.
 */
function mapCacheGet(key) {
  return getMapData(this, key).get(key);
}

/**
 * Checks if a map value for `key` exists.
 *
 * @private
 * @name has
 * @memberOf MapCache
 * @param {string} key The key of the entry to check.
 * @returns {boolean} Returns `true` if an entry for `key` exists, else `false`.
 */
function mapCacheHas(key) {
  return getMapData(this, key).has(key);
}

/**
 * Sets the map `key` to `value`.
 *
 * @private
 * @name set
 * @memberOf MapCache
 * @param {string} key The key of the value to set.
 * @param {*} value The value to set.
 * @returns {Object} Returns the map cache instance.
 */
function mapCacheSet(key, value) {
  var data = getMapData(this, key),
      size = data.size;

  data.set(key, value);
  this.size += data.size == size ? 0 : 1;
  return this;
}

/**
 * Creates a map cache object to store key-value pairs.
 *
 * @private
 * @constructor
 * @param {Array} [entries] The key-value pairs to cache.
 */
function MapCache(entries) {
  var index = -1,
      length = entries == null ? 0 : entries.length;

  this.clear();
  while (++index < length) {
    var entry = entries[index];
    this.set(entry[0], entry[1]);
  }
}

// Add methods to `MapCache`.
MapCache.prototype.clear = mapCacheClear;
MapCache.prototype['delete'] = mapCacheDelete;
MapCache.prototype.get = mapCacheGet;
MapCache.prototype.has = mapCacheHas;
MapCache.prototype.set = mapCacheSet;

/** Error message constants. */
var FUNC_ERROR_TEXT = 'Expected a function';

/**
 * Creates a function that memoizes the result of `func`. If `resolver` is
 * provided, it determines the cache key for storing the result based on the
 * arguments provided to the memoized function. By default, the first argument
 * provided to the memoized function is used as the map cache key. The `func`
 * is invoked with the `this` binding of the memoized function.
 *
 * **Note:** The cache is exposed as the `cache` property on the memoized
 * function. Its creation may be customized by replacing the `_.memoize.Cache`
 * constructor with one whose instances implement the
 * [`Map`](http://ecma-international.org/ecma-262/7.0/#sec-properties-of-the-map-prototype-object)
 * method interface of `clear`, `delete`, `get`, `has`, and `set`.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Function
 * @param {Function} func The function to have its output memoized.
 * @param {Function} [resolver] The function to resolve the cache key.
 * @returns {Function} Returns the new memoized function.
 * @example
 *
 * var object = { 'a': 1, 'b': 2 };
 * var other = { 'c': 3, 'd': 4 };
 *
 * var values = _.memoize(_.values);
 * values(object);
 * // => [1, 2]
 *
 * values(other);
 * // => [3, 4]
 *
 * object.a = 2;
 * values(object);
 * // => [1, 2]
 *
 * // Modify the result cache.
 * values.cache.set(object, ['a', 'b']);
 * values(object);
 * // => ['a', 'b']
 *
 * // Replace `_.memoize.Cache`.
 * _.memoize.Cache = WeakMap;
 */
function memoize(func, resolver) {
  if (typeof func != 'function' || (resolver != null && typeof resolver != 'function')) {
    throw new TypeError(FUNC_ERROR_TEXT);
  }
  var memoized = function() {
    var args = arguments,
        key = resolver ? resolver.apply(this, args) : args[0],
        cache = memoized.cache;

    if (cache.has(key)) {
      return cache.get(key);
    }
    var result = func.apply(this, args);
    memoized.cache = cache.set(key, result) || cache;
    return result;
  };
  memoized.cache = new (memoize.Cache || MapCache);
  return memoized;
}

// Expose `MapCache`.
memoize.Cache = MapCache;

/** Used as the maximum memoize cache size. */
var MAX_MEMOIZE_SIZE = 500;

/**
 * A specialized version of `_.memoize` which clears the memoized function's
 * cache when it exceeds `MAX_MEMOIZE_SIZE`.
 *
 * @private
 * @param {Function} func The function to have its output memoized.
 * @returns {Function} Returns the new memoized function.
 */
function memoizeCapped(func) {
  var result = memoize(func, function(key) {
    if (cache.size === MAX_MEMOIZE_SIZE) {
      cache.clear();
    }
    return key;
  });

  var cache = result.cache;
  return result;
}

/** Used to match property names within property paths. */
var rePropName = /[^.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|$))/g;

/** Used to match backslashes in property paths. */
var reEscapeChar = /\\(\\)?/g;

/**
 * Converts `string` to a property path array.
 *
 * @private
 * @param {string} string The string to convert.
 * @returns {Array} Returns the property path array.
 */
var stringToPath = memoizeCapped(function(string) {
  var result = [];
  if (string.charCodeAt(0) === 46 /* . */) {
    result.push('');
  }
  string.replace(rePropName, function(match, number, quote, subString) {
    result.push(quote ? subString.replace(reEscapeChar, '$1') : (number || match));
  });
  return result;
});

/**
 * Converts `value` to a string. An empty string is returned for `null`
 * and `undefined` values. The sign of `-0` is preserved.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to convert.
 * @returns {string} Returns the converted string.
 * @example
 *
 * _.toString(null);
 * // => ''
 *
 * _.toString(-0);
 * // => '-0'
 *
 * _.toString([1, 2, 3]);
 * // => '1,2,3'
 */
function toString(value) {
  return value == null ? '' : baseToString(value);
}

/**
 * Casts `value` to a path array if it's not one.
 *
 * @private
 * @param {*} value The value to inspect.
 * @param {Object} [object] The object to query keys on.
 * @returns {Array} Returns the cast property path array.
 */
function castPath(value, object) {
  if (isArray(value)) {
    return value;
  }
  return isKey(value, object) ? [value] : stringToPath(toString(value));
}

/**
 * Converts `value` to a string key if it's not a string or symbol.
 *
 * @private
 * @param {*} value The value to inspect.
 * @returns {string|symbol} Returns the key.
 */
function toKey(value) {
  if (typeof value == 'string' || isSymbol(value)) {
    return value;
  }
  var result = (value + '');
  return (result == '0' && (1 / value) == -Infinity) ? '-0' : result;
}

/**
 * The base implementation of `_.get` without support for default values.
 *
 * @private
 * @param {Object} object The object to query.
 * @param {Array|string} path The path of the property to get.
 * @returns {*} Returns the resolved value.
 */
function baseGet(object, path) {
  path = castPath(path, object);

  var index = 0,
      length = path.length;

  while (object != null && index < length) {
    object = object[toKey(path[index++])];
  }
  return (index && index == length) ? object : undefined;
}

/**
 * Gets the value at `path` of `object`. If the resolved value is
 * `undefined`, the `defaultValue` is returned in its place.
 *
 * @static
 * @memberOf _
 * @since 3.7.0
 * @category Object
 * @param {Object} object The object to query.
 * @param {Array|string} path The path of the property to get.
 * @param {*} [defaultValue] The value returned for `undefined` resolved values.
 * @returns {*} Returns the resolved value.
 * @example
 *
 * var object = { 'a': [{ 'b': { 'c': 3 } }] };
 *
 * _.get(object, 'a[0].b.c');
 * // => 3
 *
 * _.get(object, ['a', '0', 'b', 'c']);
 * // => 3
 *
 * _.get(object, 'a.b.c', 'default');
 * // => 'default'
 */
function get(object, path, defaultValue) {
  var result = object == null ? undefined : baseGet(object, path);
  return result === undefined ? defaultValue : result;
}

/* Built-in method references that are verified to be native. */
var Set$1 = getNative(root, 'Set');

/** Used to stand-in for `undefined` hash values. */
var HASH_UNDEFINED = '__lodash_hash_undefined__';

/**
 * Adds `value` to the array cache.
 *
 * @private
 * @name add
 * @memberOf SetCache
 * @alias push
 * @param {*} value The value to cache.
 * @returns {Object} Returns the cache instance.
 */
function setCacheAdd(value) {
  this.__data__.set(value, HASH_UNDEFINED);
  return this;
}

/**
 * Checks if `value` is in the array cache.
 *
 * @private
 * @name has
 * @memberOf SetCache
 * @param {*} value The value to search for.
 * @returns {number} Returns `true` if `value` is found, else `false`.
 */
function setCacheHas(value) {
  return this.__data__.has(value);
}

/**
 *
 * Creates an array cache object to store unique values.
 *
 * @private
 * @constructor
 * @param {Array} [values] The values to cache.
 */
function SetCache(values) {
  var index = -1,
      length = values == null ? 0 : values.length;

  this.__data__ = new MapCache;
  while (++index < length) {
    this.add(values[index]);
  }
}

// Add methods to `SetCache`.
SetCache.prototype.add = SetCache.prototype.push = setCacheAdd;
SetCache.prototype.has = setCacheHas;

/**
 * Checks if a `cache` value for `key` exists.
 *
 * @private
 * @param {Object} cache The cache to query.
 * @param {string} key The key of the entry to check.
 * @returns {boolean} Returns `true` if an entry for `key` exists, else `false`.
 */
function cacheHas(cache, key) {
  return cache.has(key);
}

/**
 * Converts `set` to an array of its values.
 *
 * @private
 * @param {Object} set The set to convert.
 * @returns {Array} Returns the values.
 */
function setToArray(set) {
  var index = -1,
      result = Array(set.size);

  set.forEach(function(value) {
    result[++index] = value;
  });
  return result;
}

/**
 * The base implementation of `_.set`.
 *
 * @private
 * @param {Object} object The object to modify.
 * @param {Array|string} path The path of the property to set.
 * @param {*} value The value to set.
 * @param {Function} [customizer] The function to customize path creation.
 * @returns {Object} Returns `object`.
 */
function baseSet(object, path, value, customizer) {
  if (!isObject(object)) {
    return object;
  }
  path = castPath(path, object);

  var index = -1,
      length = path.length,
      lastIndex = length - 1,
      nested = object;

  while (nested != null && ++index < length) {
    var key = toKey(path[index]),
        newValue = value;

    if (key === '__proto__' || key === 'constructor' || key === 'prototype') {
      return object;
    }

    if (index != lastIndex) {
      var objValue = nested[key];
      newValue = undefined;
      if (newValue === undefined) {
        newValue = isObject(objValue)
          ? objValue
          : (isIndex(path[index + 1]) ? [] : {});
      }
    }
    assignValue(nested, key, newValue);
    nested = nested[key];
  }
  return object;
}

/**
 * Sets the value at `path` of `object`. If a portion of `path` doesn't exist,
 * it's created. Arrays are created for missing index properties while objects
 * are created for all other missing properties. Use `_.setWith` to customize
 * `path` creation.
 *
 * **Note:** This method mutates `object`.
 *
 * @static
 * @memberOf _
 * @since 3.7.0
 * @category Object
 * @param {Object} object The object to modify.
 * @param {Array|string} path The path of the property to set.
 * @param {*} value The value to set.
 * @returns {Object} Returns `object`.
 * @example
 *
 * var object = { 'a': [{ 'b': { 'c': 3 } }] };
 *
 * _.set(object, 'a[0].b.c', 4);
 * console.log(object.a[0].b.c);
 * // => 4
 *
 * _.set(object, ['x', '0', 'y', 'z'], 5);
 * console.log(object.x[0].y.z);
 * // => 5
 */
function set(object, path, value) {
  return object == null ? object : baseSet(object, path, value);
}

/** Used as references for various `Number` constants. */
var INFINITY = 1 / 0;

/**
 * Creates a set object of `values`.
 *
 * @private
 * @param {Array} values The values to add to the set.
 * @returns {Object} Returns the new set.
 */
var createSet = !(Set$1 && (1 / setToArray(new Set$1([,-0]))[1]) == INFINITY) ? noop : function(values) {
  return new Set$1(values);
};

/** Used as the size to enable large array optimizations. */
var LARGE_ARRAY_SIZE = 200;

/**
 * The base implementation of `_.uniqBy` without support for iteratee shorthands.
 *
 * @private
 * @param {Array} array The array to inspect.
 * @param {Function} [iteratee] The iteratee invoked per element.
 * @param {Function} [comparator] The comparator invoked per element.
 * @returns {Array} Returns the new duplicate free array.
 */
function baseUniq(array, iteratee, comparator) {
  var index = -1,
      includes = arrayIncludes,
      length = array.length,
      isCommon = true,
      result = [],
      seen = result;

  if (length >= LARGE_ARRAY_SIZE) {
    var set = createSet(array);
    if (set) {
      return setToArray(set);
    }
    isCommon = false;
    includes = cacheHas;
    seen = new SetCache;
  }
  else {
    seen = result;
  }
  outer:
  while (++index < length) {
    var value = array[index],
        computed = value;

    value = (value !== 0) ? value : 0;
    if (isCommon && computed === computed) {
      var seenIndex = seen.length;
      while (seenIndex--) {
        if (seen[seenIndex] === computed) {
          continue outer;
        }
      }
      result.push(value);
    }
    else if (!includes(seen, computed, comparator)) {
      if (seen !== result) {
        seen.push(computed);
      }
      result.push(value);
    }
  }
  return result;
}

/**
 * Creates a duplicate-free version of an array, using
 * [`SameValueZero`](http://ecma-international.org/ecma-262/7.0/#sec-samevaluezero)
 * for equality comparisons, in which only the first occurrence of each element
 * is kept. The order of result values is determined by the order they occur
 * in the array.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Array
 * @param {Array} array The array to inspect.
 * @returns {Array} Returns the new duplicate free array.
 * @example
 *
 * _.uniq([2, 1, 2]);
 * // => [2, 1]
 */
function uniq(array) {
  return (array && array.length) ? baseUniq(array) : [];
}

function mergeOptions(base, ...overrides) {
  if (!overrides.length) {
    return base;
  }
  for (const override of overrides) {
    if (!override) {
      continue;
    }
    if (typeof override === "function") {
      base = override(base) ?? base;
    } else {
      base = vite.mergeConfig(base, override);
    }
  }
  return base;
}
function show(data, depth = 10) {
  console.log(node_util.inspect(data, { depth, colors: true }));
}

class ConfigBuilder {
  constructor(config, env, fusionOptions) {
    this.config = config;
    this.env = env;
    this.fusionOptions = fusionOptions;
    this.config = vite.mergeConfig(
      {
        define: {
          __VUE_OPTIONS_API__: "false",
          __VUE_PROD_DEVTOOLS__: "true",
          __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: "false"
        },
        build: {
          manifest: "manifest.json",
          rollupOptions: {
            preserveEntrySignatures: "strict",
            input: {},
            output: this.getDefaultOutput()
            // external: (source: string, importer: string | undefined, isResolved: boolean) => {
            //   for (const external of this.externals) {
            //     const result = external(source, importer, isResolved);
            //
            //     if (result) {
            //       return true;
            //     }
            //   }
            // },
          },
          emptyOutDir: false,
          sourcemap: env.mode !== "production" ? "inline" : false
        },
        plugins: [],
        css: {
          devSourcemap: true
        },
        esbuild: {
          // Todo: Remove if esbuild supports decorators by default
          target: "es2022"
        }
      },
      this.config
    );
    this.addTask("hidden:placeholder");
  }
  server = null;
  static globalOverrideConfig = {};
  overrideConfig = {};
  entryFileNamesCallbacks = [];
  chunkFileNamesCallbacks = [];
  assetFileNamesCallbacks = [];
  moveTasks = [];
  copyTasks = [];
  linkTasks = [];
  postBuildCallbacks = [];
  resolveIdCallbacks = [];
  loadCallbacks = [];
  // fileNameMap: Record<string, string> = {};
  // externals: ((source: string, importer: string | undefined, isResolved: boolean) => boolean | string | NullValue)[] = [];
  watches = [];
  cleans = [];
  tasks = /* @__PURE__ */ new Map();
  merge(override) {
    if (typeof override === "function") {
      this.config = override(this.config) ?? this.config;
      return this;
    }
    this.config = vite.mergeConfig(this.config, override);
    return this;
  }
  getDefaultOutput() {
    let serial = 0;
    return {
      entryFileNames: (chunkInfo) => {
        const name = this.getChunkNameFromTask(chunkInfo);
        if (name) {
          return name;
        }
        for (const entryFileNamesCallback of this.entryFileNamesCallbacks) {
          const name2 = entryFileNamesCallback(chunkInfo);
          if (name2) {
            return name2;
          }
        }
        return "[name].js";
      },
      chunkFileNames: (chunkInfo) => {
        serial++;
        const name = this.getChunkNameFromTask(chunkInfo);
        if (name) {
          return name;
        }
        for (const chunkFileNamesCallback of this.chunkFileNamesCallbacks) {
          const name2 = chunkFileNamesCallback(chunkInfo);
          if (name2) {
            return name2;
          }
        }
        const chunkDir = this.getChunkDir();
        if (this.env.mode === "production" && this.fusionOptions.chunkNameObfuscation) {
          return `${chunkDir}${serial}.js`;
        }
        return `${chunkDir}[name]-[hash].js`;
      },
      assetFileNames: (assetInfo) => {
        for (const assetFileNamesCallback of this.assetFileNamesCallbacks) {
          const name = assetFileNamesCallback(assetInfo);
          if (name) {
            return name;
          }
        }
        return "[name].[ext]";
      }
    };
  }
  getChunkDir() {
    let chunkDir = this.fusionOptions.chunkDir ?? "chunks";
    chunkDir.replace(/\\/g, "/");
    if (chunkDir && !chunkDir.endsWith("/")) {
      chunkDir += "/";
    }
    if (chunkDir === "./" || chunkDir === "/") {
      chunkDir = "";
    }
    return chunkDir;
  }
  getChunkNameFromTask(chunkInfo) {
    if (this.tasks.has(chunkInfo.name)) {
      const output = this.tasks.get(chunkInfo.name)?.output;
      if (output) {
        const name = typeof output === "function" ? output(chunkInfo) : output;
        if (!node_path.isAbsolute(name)) {
          return name;
        }
      }
    }
    return void 0;
  }
  ensurePath(path, def = {}) {
    if (get(this.config, path) == null) {
      set(this.config, path, def);
    }
    return this;
  }
  get(path) {
    return get(this.config, path);
  }
  set(path, value) {
    set(this.config, path, value);
    return this;
  }
  addTask(input, group) {
    const task = new BuildTask(input, group);
    this.tasks.set(task.id, task);
    const inputOptions = this.config.build.rollupOptions.input;
    inputOptions[task.id] = task.input;
    return task;
  }
  addCleans(...paths) {
    this.cleans.push(...paths);
    return this;
  }
  // addExternals(externals: Externalize) {
  //   if (Array.isArray(externals)) {
  //     this.externals.push((rollupOptions) => {
  //       rollupOptions.external
  //     })
  //   } else if (typeof externals === 'object') {
  //
  //   } else {
  //
  //   }
  // }
  // addPlugin(plugin: PluginOption) {
  //   this.config.plugins?.push(plugin);
  // }
  //
  // removePlugin(plugin: string | PluginOption) {
  //   this.config.plugins = this.config.plugins?.filter((p) => {
  //     if (!p) {
  //       return true;
  //     }
  //
  //     if (typeof plugin === 'string' && typeof p === 'object' && 'name' in p) {
  //       return p.name !== plugin;
  //     } else if (typeof plugin === 'object' && typeof p === 'object') {
  //       return p !== plugin;
  //     }
  //
  //     return true;
  //   });
  // }
  relativePath(to) {
    return node_path.relative(process.cwd(), to);
  }
  debug() {
    show(this.config);
  }
}

function getArgsAfterDoubleDashes(argv) {
  argv ??= process.argv;
  return argv.slice(2).join(" ").split(" -- ").slice(1).join(" -- ").trim().split(" ").filter((v) => v !== "");
}
function parseArgv(argv) {
  const app = yargs();
  app.option("cwd", {
    type: "string",
    description: "Current working directory"
  });
  app.option("list", {
    alias: "l",
    type: "boolean",
    description: "List all available tasks"
  });
  app.option("config", {
    alias: "c",
    type: "string",
    description: "Path to config file"
  });
  app.option("server-file", {
    alias: "s",
    type: "string",
    description: "Path to server file"
  });
  app.option("verbose", {
    alias: "v",
    type: "count",
    description: "Increase verbosity of output. Use multiple times for more verbosity."
  });
  return app.parseSync(argv);
}

function getDefaultExportFromCjs (x) {
	return x && x.__esModule && Object.prototype.hasOwnProperty.call(x, 'default') ? x['default'] : x;
}

var main;
var hasRequiredMain;

function requireMain () {
	if (hasRequiredMain) return main;
	hasRequiredMain = 1;
	var __defProp = Object.defineProperty;
	var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
	var __getOwnPropNames = Object.getOwnPropertyNames;
	var __hasOwnProp = Object.prototype.hasOwnProperty;
	var __export = (target, all) => {
	  for (var name in all)
	    __defProp(target, name, { get: all[name], enumerable: true });
	};
	var __copyProps = (to, from, except, desc) => {
	  if (from && typeof from === "object" || typeof from === "function") {
	    for (let key of __getOwnPropNames(from))
	      if (!__hasOwnProp.call(to, key) && key !== except)
	        __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
	  }
	  return to;
	};
	var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

	// lib/npm/node.ts
	var node_exports = {};
	__export(node_exports, {
	  analyzeMetafile: () => analyzeMetafile,
	  analyzeMetafileSync: () => analyzeMetafileSync,
	  build: () => build,
	  buildSync: () => buildSync,
	  context: () => context,
	  default: () => node_default,
	  formatMessages: () => formatMessages,
	  formatMessagesSync: () => formatMessagesSync,
	  initialize: () => initialize,
	  stop: () => stop,
	  transform: () => transform,
	  transformSync: () => transformSync,
	  version: () => version
	});
	main = __toCommonJS(node_exports);

	// lib/shared/stdio_protocol.ts
	function encodePacket(packet) {
	  let visit = (value) => {
	    if (value === null) {
	      bb.write8(0);
	    } else if (typeof value === "boolean") {
	      bb.write8(1);
	      bb.write8(+value);
	    } else if (typeof value === "number") {
	      bb.write8(2);
	      bb.write32(value | 0);
	    } else if (typeof value === "string") {
	      bb.write8(3);
	      bb.write(encodeUTF8(value));
	    } else if (value instanceof Uint8Array) {
	      bb.write8(4);
	      bb.write(value);
	    } else if (value instanceof Array) {
	      bb.write8(5);
	      bb.write32(value.length);
	      for (let item of value) {
	        visit(item);
	      }
	    } else {
	      let keys = Object.keys(value);
	      bb.write8(6);
	      bb.write32(keys.length);
	      for (let key of keys) {
	        bb.write(encodeUTF8(key));
	        visit(value[key]);
	      }
	    }
	  };
	  let bb = new ByteBuffer();
	  bb.write32(0);
	  bb.write32(packet.id << 1 | +!packet.isRequest);
	  visit(packet.value);
	  writeUInt32LE(bb.buf, bb.len - 4, 0);
	  return bb.buf.subarray(0, bb.len);
	}
	function decodePacket(bytes) {
	  let visit = () => {
	    switch (bb.read8()) {
	      case 0:
	        return null;
	      case 1:
	        return !!bb.read8();
	      case 2:
	        return bb.read32();
	      case 3:
	        return decodeUTF8(bb.read());
	      case 4:
	        return bb.read();
	      case 5: {
	        let count = bb.read32();
	        let value2 = [];
	        for (let i = 0; i < count; i++) {
	          value2.push(visit());
	        }
	        return value2;
	      }
	      case 6: {
	        let count = bb.read32();
	        let value2 = {};
	        for (let i = 0; i < count; i++) {
	          value2[decodeUTF8(bb.read())] = visit();
	        }
	        return value2;
	      }
	      default:
	        throw new Error("Invalid packet");
	    }
	  };
	  let bb = new ByteBuffer(bytes);
	  let id = bb.read32();
	  let isRequest = (id & 1) === 0;
	  id >>>= 1;
	  let value = visit();
	  if (bb.ptr !== bytes.length) {
	    throw new Error("Invalid packet");
	  }
	  return { id, isRequest, value };
	}
	var ByteBuffer = class {
	  constructor(buf = new Uint8Array(1024)) {
	    this.buf = buf;
	    this.len = 0;
	    this.ptr = 0;
	  }
	  _write(delta) {
	    if (this.len + delta > this.buf.length) {
	      let clone = new Uint8Array((this.len + delta) * 2);
	      clone.set(this.buf);
	      this.buf = clone;
	    }
	    this.len += delta;
	    return this.len - delta;
	  }
	  write8(value) {
	    let offset = this._write(1);
	    this.buf[offset] = value;
	  }
	  write32(value) {
	    let offset = this._write(4);
	    writeUInt32LE(this.buf, value, offset);
	  }
	  write(bytes) {
	    let offset = this._write(4 + bytes.length);
	    writeUInt32LE(this.buf, bytes.length, offset);
	    this.buf.set(bytes, offset + 4);
	  }
	  _read(delta) {
	    if (this.ptr + delta > this.buf.length) {
	      throw new Error("Invalid packet");
	    }
	    this.ptr += delta;
	    return this.ptr - delta;
	  }
	  read8() {
	    return this.buf[this._read(1)];
	  }
	  read32() {
	    return readUInt32LE(this.buf, this._read(4));
	  }
	  read() {
	    let length = this.read32();
	    let bytes = new Uint8Array(length);
	    let ptr = this._read(bytes.length);
	    bytes.set(this.buf.subarray(ptr, ptr + length));
	    return bytes;
	  }
	};
	var encodeUTF8;
	var decodeUTF8;
	var encodeInvariant;
	if (typeof TextEncoder !== "undefined" && typeof TextDecoder !== "undefined") {
	  let encoder = new TextEncoder();
	  let decoder = new TextDecoder();
	  encodeUTF8 = (text) => encoder.encode(text);
	  decodeUTF8 = (bytes) => decoder.decode(bytes);
	  encodeInvariant = 'new TextEncoder().encode("")';
	} else if (typeof Buffer !== "undefined") {
	  encodeUTF8 = (text) => Buffer.from(text);
	  decodeUTF8 = (bytes) => {
	    let { buffer, byteOffset, byteLength } = bytes;
	    return Buffer.from(buffer, byteOffset, byteLength).toString();
	  };
	  encodeInvariant = 'Buffer.from("")';
	} else {
	  throw new Error("No UTF-8 codec found");
	}
	if (!(encodeUTF8("") instanceof Uint8Array))
	  throw new Error(`Invariant violation: "${encodeInvariant} instanceof Uint8Array" is incorrectly false

This indicates that your JavaScript environment is broken. You cannot use
esbuild in this environment because esbuild relies on this invariant. This
is not a problem with esbuild. You need to fix your environment instead.
`);
	function readUInt32LE(buffer, offset) {
	  return buffer[offset++] | buffer[offset++] << 8 | buffer[offset++] << 16 | buffer[offset++] << 24;
	}
	function writeUInt32LE(buffer, value, offset) {
	  buffer[offset++] = value;
	  buffer[offset++] = value >> 8;
	  buffer[offset++] = value >> 16;
	  buffer[offset++] = value >> 24;
	}

	// lib/shared/common.ts
	var quote = JSON.stringify;
	var buildLogLevelDefault = "warning";
	var transformLogLevelDefault = "silent";
	function validateAndJoinStringArray(values, what) {
	  const toJoin = [];
	  for (const value of values) {
	    validateStringValue(value, what);
	    if (value.indexOf(",") >= 0) throw new Error(`Invalid ${what}: ${value}`);
	    toJoin.push(value);
	  }
	  return toJoin.join(",");
	}
	var canBeAnything = () => null;
	var mustBeBoolean = (value) => typeof value === "boolean" ? null : "a boolean";
	var mustBeString = (value) => typeof value === "string" ? null : "a string";
	var mustBeRegExp = (value) => value instanceof RegExp ? null : "a RegExp object";
	var mustBeInteger = (value) => typeof value === "number" && value === (value | 0) ? null : "an integer";
	var mustBeValidPortNumber = (value) => typeof value === "number" && value === (value | 0) && value >= 0 && value <= 65535 ? null : "a valid port number";
	var mustBeFunction = (value) => typeof value === "function" ? null : "a function";
	var mustBeArray = (value) => Array.isArray(value) ? null : "an array";
	var mustBeArrayOfStrings = (value) => Array.isArray(value) && value.every((x) => typeof x === "string") ? null : "an array of strings";
	var mustBeObject = (value) => typeof value === "object" && value !== null && !Array.isArray(value) ? null : "an object";
	var mustBeEntryPoints = (value) => typeof value === "object" && value !== null ? null : "an array or an object";
	var mustBeWebAssemblyModule = (value) => value instanceof WebAssembly.Module ? null : "a WebAssembly.Module";
	var mustBeObjectOrNull = (value) => typeof value === "object" && !Array.isArray(value) ? null : "an object or null";
	var mustBeStringOrBoolean = (value) => typeof value === "string" || typeof value === "boolean" ? null : "a string or a boolean";
	var mustBeStringOrObject = (value) => typeof value === "string" || typeof value === "object" && value !== null && !Array.isArray(value) ? null : "a string or an object";
	var mustBeStringOrArrayOfStrings = (value) => typeof value === "string" || Array.isArray(value) && value.every((x) => typeof x === "string") ? null : "a string or an array of strings";
	var mustBeStringOrUint8Array = (value) => typeof value === "string" || value instanceof Uint8Array ? null : "a string or a Uint8Array";
	var mustBeStringOrURL = (value) => typeof value === "string" || value instanceof URL ? null : "a string or a URL";
	function getFlag(object, keys, key, mustBeFn) {
	  let value = object[key];
	  keys[key + ""] = true;
	  if (value === void 0) return void 0;
	  let mustBe = mustBeFn(value);
	  if (mustBe !== null) throw new Error(`${quote(key)} must be ${mustBe}`);
	  return value;
	}
	function checkForInvalidFlags(object, keys, where) {
	  for (let key in object) {
	    if (!(key in keys)) {
	      throw new Error(`Invalid option ${where}: ${quote(key)}`);
	    }
	  }
	}
	function validateInitializeOptions(options) {
	  let keys = /* @__PURE__ */ Object.create(null);
	  let wasmURL = getFlag(options, keys, "wasmURL", mustBeStringOrURL);
	  let wasmModule = getFlag(options, keys, "wasmModule", mustBeWebAssemblyModule);
	  let worker = getFlag(options, keys, "worker", mustBeBoolean);
	  checkForInvalidFlags(options, keys, "in initialize() call");
	  return {
	    wasmURL,
	    wasmModule,
	    worker
	  };
	}
	function validateMangleCache(mangleCache) {
	  let validated;
	  if (mangleCache !== void 0) {
	    validated = /* @__PURE__ */ Object.create(null);
	    for (let key in mangleCache) {
	      let value = mangleCache[key];
	      if (typeof value === "string" || value === false) {
	        validated[key] = value;
	      } else {
	        throw new Error(`Expected ${quote(key)} in mangle cache to map to either a string or false`);
	      }
	    }
	  }
	  return validated;
	}
	function pushLogFlags(flags, options, keys, isTTY2, logLevelDefault) {
	  let color = getFlag(options, keys, "color", mustBeBoolean);
	  let logLevel = getFlag(options, keys, "logLevel", mustBeString);
	  let logLimit = getFlag(options, keys, "logLimit", mustBeInteger);
	  if (color !== void 0) flags.push(`--color=${color}`);
	  else if (isTTY2) flags.push(`--color=true`);
	  flags.push(`--log-level=${logLevel || logLevelDefault}`);
	  flags.push(`--log-limit=${logLimit || 0}`);
	}
	function validateStringValue(value, what, key) {
	  if (typeof value !== "string") {
	    throw new Error(`Expected value for ${what}${key !== void 0 ? " " + quote(key) : ""} to be a string, got ${typeof value} instead`);
	  }
	  return value;
	}
	function pushCommonFlags(flags, options, keys) {
	  let legalComments = getFlag(options, keys, "legalComments", mustBeString);
	  let sourceRoot = getFlag(options, keys, "sourceRoot", mustBeString);
	  let sourcesContent = getFlag(options, keys, "sourcesContent", mustBeBoolean);
	  let target = getFlag(options, keys, "target", mustBeStringOrArrayOfStrings);
	  let format = getFlag(options, keys, "format", mustBeString);
	  let globalName = getFlag(options, keys, "globalName", mustBeString);
	  let mangleProps = getFlag(options, keys, "mangleProps", mustBeRegExp);
	  let reserveProps = getFlag(options, keys, "reserveProps", mustBeRegExp);
	  let mangleQuoted = getFlag(options, keys, "mangleQuoted", mustBeBoolean);
	  let minify = getFlag(options, keys, "minify", mustBeBoolean);
	  let minifySyntax = getFlag(options, keys, "minifySyntax", mustBeBoolean);
	  let minifyWhitespace = getFlag(options, keys, "minifyWhitespace", mustBeBoolean);
	  let minifyIdentifiers = getFlag(options, keys, "minifyIdentifiers", mustBeBoolean);
	  let lineLimit = getFlag(options, keys, "lineLimit", mustBeInteger);
	  let drop = getFlag(options, keys, "drop", mustBeArrayOfStrings);
	  let dropLabels = getFlag(options, keys, "dropLabels", mustBeArrayOfStrings);
	  let charset = getFlag(options, keys, "charset", mustBeString);
	  let treeShaking = getFlag(options, keys, "treeShaking", mustBeBoolean);
	  let ignoreAnnotations = getFlag(options, keys, "ignoreAnnotations", mustBeBoolean);
	  let jsx = getFlag(options, keys, "jsx", mustBeString);
	  let jsxFactory = getFlag(options, keys, "jsxFactory", mustBeString);
	  let jsxFragment = getFlag(options, keys, "jsxFragment", mustBeString);
	  let jsxImportSource = getFlag(options, keys, "jsxImportSource", mustBeString);
	  let jsxDev = getFlag(options, keys, "jsxDev", mustBeBoolean);
	  let jsxSideEffects = getFlag(options, keys, "jsxSideEffects", mustBeBoolean);
	  let define = getFlag(options, keys, "define", mustBeObject);
	  let logOverride = getFlag(options, keys, "logOverride", mustBeObject);
	  let supported = getFlag(options, keys, "supported", mustBeObject);
	  let pure = getFlag(options, keys, "pure", mustBeArrayOfStrings);
	  let keepNames = getFlag(options, keys, "keepNames", mustBeBoolean);
	  let platform = getFlag(options, keys, "platform", mustBeString);
	  let tsconfigRaw = getFlag(options, keys, "tsconfigRaw", mustBeStringOrObject);
	  let absPaths = getFlag(options, keys, "absPaths", mustBeArrayOfStrings);
	  if (legalComments) flags.push(`--legal-comments=${legalComments}`);
	  if (sourceRoot !== void 0) flags.push(`--source-root=${sourceRoot}`);
	  if (sourcesContent !== void 0) flags.push(`--sources-content=${sourcesContent}`);
	  if (target) flags.push(`--target=${validateAndJoinStringArray(Array.isArray(target) ? target : [target], "target")}`);
	  if (format) flags.push(`--format=${format}`);
	  if (globalName) flags.push(`--global-name=${globalName}`);
	  if (platform) flags.push(`--platform=${platform}`);
	  if (tsconfigRaw) flags.push(`--tsconfig-raw=${typeof tsconfigRaw === "string" ? tsconfigRaw : JSON.stringify(tsconfigRaw)}`);
	  if (minify) flags.push("--minify");
	  if (minifySyntax) flags.push("--minify-syntax");
	  if (minifyWhitespace) flags.push("--minify-whitespace");
	  if (minifyIdentifiers) flags.push("--minify-identifiers");
	  if (lineLimit) flags.push(`--line-limit=${lineLimit}`);
	  if (charset) flags.push(`--charset=${charset}`);
	  if (treeShaking !== void 0) flags.push(`--tree-shaking=${treeShaking}`);
	  if (ignoreAnnotations) flags.push(`--ignore-annotations`);
	  if (drop) for (let what of drop) flags.push(`--drop:${validateStringValue(what, "drop")}`);
	  if (dropLabels) flags.push(`--drop-labels=${validateAndJoinStringArray(dropLabels, "drop label")}`);
	  if (absPaths) flags.push(`--abs-paths=${validateAndJoinStringArray(absPaths, "abs paths")}`);
	  if (mangleProps) flags.push(`--mangle-props=${jsRegExpToGoRegExp(mangleProps)}`);
	  if (reserveProps) flags.push(`--reserve-props=${jsRegExpToGoRegExp(reserveProps)}`);
	  if (mangleQuoted !== void 0) flags.push(`--mangle-quoted=${mangleQuoted}`);
	  if (jsx) flags.push(`--jsx=${jsx}`);
	  if (jsxFactory) flags.push(`--jsx-factory=${jsxFactory}`);
	  if (jsxFragment) flags.push(`--jsx-fragment=${jsxFragment}`);
	  if (jsxImportSource) flags.push(`--jsx-import-source=${jsxImportSource}`);
	  if (jsxDev) flags.push(`--jsx-dev`);
	  if (jsxSideEffects) flags.push(`--jsx-side-effects`);
	  if (define) {
	    for (let key in define) {
	      if (key.indexOf("=") >= 0) throw new Error(`Invalid define: ${key}`);
	      flags.push(`--define:${key}=${validateStringValue(define[key], "define", key)}`);
	    }
	  }
	  if (logOverride) {
	    for (let key in logOverride) {
	      if (key.indexOf("=") >= 0) throw new Error(`Invalid log override: ${key}`);
	      flags.push(`--log-override:${key}=${validateStringValue(logOverride[key], "log override", key)}`);
	    }
	  }
	  if (supported) {
	    for (let key in supported) {
	      if (key.indexOf("=") >= 0) throw new Error(`Invalid supported: ${key}`);
	      const value = supported[key];
	      if (typeof value !== "boolean") throw new Error(`Expected value for supported ${quote(key)} to be a boolean, got ${typeof value} instead`);
	      flags.push(`--supported:${key}=${value}`);
	    }
	  }
	  if (pure) for (let fn of pure) flags.push(`--pure:${validateStringValue(fn, "pure")}`);
	  if (keepNames) flags.push(`--keep-names`);
	}
	function flagsForBuildOptions(callName, options, isTTY2, logLevelDefault, writeDefault) {
	  var _a2;
	  let flags = [];
	  let entries = [];
	  let keys = /* @__PURE__ */ Object.create(null);
	  let stdinContents = null;
	  let stdinResolveDir = null;
	  pushLogFlags(flags, options, keys, isTTY2, logLevelDefault);
	  pushCommonFlags(flags, options, keys);
	  let sourcemap = getFlag(options, keys, "sourcemap", mustBeStringOrBoolean);
	  let bundle = getFlag(options, keys, "bundle", mustBeBoolean);
	  let splitting = getFlag(options, keys, "splitting", mustBeBoolean);
	  let preserveSymlinks = getFlag(options, keys, "preserveSymlinks", mustBeBoolean);
	  let metafile = getFlag(options, keys, "metafile", mustBeBoolean);
	  let outfile = getFlag(options, keys, "outfile", mustBeString);
	  let outdir = getFlag(options, keys, "outdir", mustBeString);
	  let outbase = getFlag(options, keys, "outbase", mustBeString);
	  let tsconfig = getFlag(options, keys, "tsconfig", mustBeString);
	  let resolveExtensions = getFlag(options, keys, "resolveExtensions", mustBeArrayOfStrings);
	  let nodePathsInput = getFlag(options, keys, "nodePaths", mustBeArrayOfStrings);
	  let mainFields = getFlag(options, keys, "mainFields", mustBeArrayOfStrings);
	  let conditions = getFlag(options, keys, "conditions", mustBeArrayOfStrings);
	  let external = getFlag(options, keys, "external", mustBeArrayOfStrings);
	  let packages = getFlag(options, keys, "packages", mustBeString);
	  let alias = getFlag(options, keys, "alias", mustBeObject);
	  let loader = getFlag(options, keys, "loader", mustBeObject);
	  let outExtension = getFlag(options, keys, "outExtension", mustBeObject);
	  let publicPath = getFlag(options, keys, "publicPath", mustBeString);
	  let entryNames = getFlag(options, keys, "entryNames", mustBeString);
	  let chunkNames = getFlag(options, keys, "chunkNames", mustBeString);
	  let assetNames = getFlag(options, keys, "assetNames", mustBeString);
	  let inject = getFlag(options, keys, "inject", mustBeArrayOfStrings);
	  let banner = getFlag(options, keys, "banner", mustBeObject);
	  let footer = getFlag(options, keys, "footer", mustBeObject);
	  let entryPoints = getFlag(options, keys, "entryPoints", mustBeEntryPoints);
	  let absWorkingDir = getFlag(options, keys, "absWorkingDir", mustBeString);
	  let stdin = getFlag(options, keys, "stdin", mustBeObject);
	  let write = (_a2 = getFlag(options, keys, "write", mustBeBoolean)) != null ? _a2 : writeDefault;
	  let allowOverwrite = getFlag(options, keys, "allowOverwrite", mustBeBoolean);
	  let mangleCache = getFlag(options, keys, "mangleCache", mustBeObject);
	  keys.plugins = true;
	  checkForInvalidFlags(options, keys, `in ${callName}() call`);
	  if (sourcemap) flags.push(`--sourcemap${sourcemap === true ? "" : `=${sourcemap}`}`);
	  if (bundle) flags.push("--bundle");
	  if (allowOverwrite) flags.push("--allow-overwrite");
	  if (splitting) flags.push("--splitting");
	  if (preserveSymlinks) flags.push("--preserve-symlinks");
	  if (metafile) flags.push(`--metafile`);
	  if (outfile) flags.push(`--outfile=${outfile}`);
	  if (outdir) flags.push(`--outdir=${outdir}`);
	  if (outbase) flags.push(`--outbase=${outbase}`);
	  if (tsconfig) flags.push(`--tsconfig=${tsconfig}`);
	  if (packages) flags.push(`--packages=${packages}`);
	  if (resolveExtensions) flags.push(`--resolve-extensions=${validateAndJoinStringArray(resolveExtensions, "resolve extension")}`);
	  if (publicPath) flags.push(`--public-path=${publicPath}`);
	  if (entryNames) flags.push(`--entry-names=${entryNames}`);
	  if (chunkNames) flags.push(`--chunk-names=${chunkNames}`);
	  if (assetNames) flags.push(`--asset-names=${assetNames}`);
	  if (mainFields) flags.push(`--main-fields=${validateAndJoinStringArray(mainFields, "main field")}`);
	  if (conditions) flags.push(`--conditions=${validateAndJoinStringArray(conditions, "condition")}`);
	  if (external) for (let name of external) flags.push(`--external:${validateStringValue(name, "external")}`);
	  if (alias) {
	    for (let old in alias) {
	      if (old.indexOf("=") >= 0) throw new Error(`Invalid package name in alias: ${old}`);
	      flags.push(`--alias:${old}=${validateStringValue(alias[old], "alias", old)}`);
	    }
	  }
	  if (banner) {
	    for (let type in banner) {
	      if (type.indexOf("=") >= 0) throw new Error(`Invalid banner file type: ${type}`);
	      flags.push(`--banner:${type}=${validateStringValue(banner[type], "banner", type)}`);
	    }
	  }
	  if (footer) {
	    for (let type in footer) {
	      if (type.indexOf("=") >= 0) throw new Error(`Invalid footer file type: ${type}`);
	      flags.push(`--footer:${type}=${validateStringValue(footer[type], "footer", type)}`);
	    }
	  }
	  if (inject) for (let path3 of inject) flags.push(`--inject:${validateStringValue(path3, "inject")}`);
	  if (loader) {
	    for (let ext in loader) {
	      if (ext.indexOf("=") >= 0) throw new Error(`Invalid loader extension: ${ext}`);
	      flags.push(`--loader:${ext}=${validateStringValue(loader[ext], "loader", ext)}`);
	    }
	  }
	  if (outExtension) {
	    for (let ext in outExtension) {
	      if (ext.indexOf("=") >= 0) throw new Error(`Invalid out extension: ${ext}`);
	      flags.push(`--out-extension:${ext}=${validateStringValue(outExtension[ext], "out extension", ext)}`);
	    }
	  }
	  if (entryPoints) {
	    if (Array.isArray(entryPoints)) {
	      for (let i = 0, n = entryPoints.length; i < n; i++) {
	        let entryPoint = entryPoints[i];
	        if (typeof entryPoint === "object" && entryPoint !== null) {
	          let entryPointKeys = /* @__PURE__ */ Object.create(null);
	          let input = getFlag(entryPoint, entryPointKeys, "in", mustBeString);
	          let output = getFlag(entryPoint, entryPointKeys, "out", mustBeString);
	          checkForInvalidFlags(entryPoint, entryPointKeys, "in entry point at index " + i);
	          if (input === void 0) throw new Error('Missing property "in" for entry point at index ' + i);
	          if (output === void 0) throw new Error('Missing property "out" for entry point at index ' + i);
	          entries.push([output, input]);
	        } else {
	          entries.push(["", validateStringValue(entryPoint, "entry point at index " + i)]);
	        }
	      }
	    } else {
	      for (let key in entryPoints) {
	        entries.push([key, validateStringValue(entryPoints[key], "entry point", key)]);
	      }
	    }
	  }
	  if (stdin) {
	    let stdinKeys = /* @__PURE__ */ Object.create(null);
	    let contents = getFlag(stdin, stdinKeys, "contents", mustBeStringOrUint8Array);
	    let resolveDir = getFlag(stdin, stdinKeys, "resolveDir", mustBeString);
	    let sourcefile = getFlag(stdin, stdinKeys, "sourcefile", mustBeString);
	    let loader2 = getFlag(stdin, stdinKeys, "loader", mustBeString);
	    checkForInvalidFlags(stdin, stdinKeys, 'in "stdin" object');
	    if (sourcefile) flags.push(`--sourcefile=${sourcefile}`);
	    if (loader2) flags.push(`--loader=${loader2}`);
	    if (resolveDir) stdinResolveDir = resolveDir;
	    if (typeof contents === "string") stdinContents = encodeUTF8(contents);
	    else if (contents instanceof Uint8Array) stdinContents = contents;
	  }
	  let nodePaths = [];
	  if (nodePathsInput) {
	    for (let value of nodePathsInput) {
	      value += "";
	      nodePaths.push(value);
	    }
	  }
	  return {
	    entries,
	    flags,
	    write,
	    stdinContents,
	    stdinResolveDir,
	    absWorkingDir,
	    nodePaths,
	    mangleCache: validateMangleCache(mangleCache)
	  };
	}
	function flagsForTransformOptions(callName, options, isTTY2, logLevelDefault) {
	  let flags = [];
	  let keys = /* @__PURE__ */ Object.create(null);
	  pushLogFlags(flags, options, keys, isTTY2, logLevelDefault);
	  pushCommonFlags(flags, options, keys);
	  let sourcemap = getFlag(options, keys, "sourcemap", mustBeStringOrBoolean);
	  let sourcefile = getFlag(options, keys, "sourcefile", mustBeString);
	  let loader = getFlag(options, keys, "loader", mustBeString);
	  let banner = getFlag(options, keys, "banner", mustBeString);
	  let footer = getFlag(options, keys, "footer", mustBeString);
	  let mangleCache = getFlag(options, keys, "mangleCache", mustBeObject);
	  checkForInvalidFlags(options, keys, `in ${callName}() call`);
	  if (sourcemap) flags.push(`--sourcemap=${sourcemap === true ? "external" : sourcemap}`);
	  if (sourcefile) flags.push(`--sourcefile=${sourcefile}`);
	  if (loader) flags.push(`--loader=${loader}`);
	  if (banner) flags.push(`--banner=${banner}`);
	  if (footer) flags.push(`--footer=${footer}`);
	  return {
	    flags,
	    mangleCache: validateMangleCache(mangleCache)
	  };
	}
	function createChannel(streamIn) {
	  const requestCallbacksByKey = {};
	  const closeData = { didClose: false, reason: "" };
	  let responseCallbacks = {};
	  let nextRequestID = 0;
	  let nextBuildKey = 0;
	  let stdout = new Uint8Array(16 * 1024);
	  let stdoutUsed = 0;
	  let readFromStdout = (chunk) => {
	    let limit = stdoutUsed + chunk.length;
	    if (limit > stdout.length) {
	      let swap = new Uint8Array(limit * 2);
	      swap.set(stdout);
	      stdout = swap;
	    }
	    stdout.set(chunk, stdoutUsed);
	    stdoutUsed += chunk.length;
	    let offset = 0;
	    while (offset + 4 <= stdoutUsed) {
	      let length = readUInt32LE(stdout, offset);
	      if (offset + 4 + length > stdoutUsed) {
	        break;
	      }
	      offset += 4;
	      handleIncomingPacket(stdout.subarray(offset, offset + length));
	      offset += length;
	    }
	    if (offset > 0) {
	      stdout.copyWithin(0, offset, stdoutUsed);
	      stdoutUsed -= offset;
	    }
	  };
	  let afterClose = (error) => {
	    closeData.didClose = true;
	    if (error) closeData.reason = ": " + (error.message || error);
	    const text = "The service was stopped" + closeData.reason;
	    for (let id in responseCallbacks) {
	      responseCallbacks[id](text, null);
	    }
	    responseCallbacks = {};
	  };
	  let sendRequest = (refs, value, callback) => {
	    if (closeData.didClose) return callback("The service is no longer running" + closeData.reason, null);
	    let id = nextRequestID++;
	    responseCallbacks[id] = (error, response) => {
	      try {
	        callback(error, response);
	      } finally {
	        if (refs) refs.unref();
	      }
	    };
	    if (refs) refs.ref();
	    streamIn.writeToStdin(encodePacket({ id, isRequest: true, value }));
	  };
	  let sendResponse = (id, value) => {
	    if (closeData.didClose) throw new Error("The service is no longer running" + closeData.reason);
	    streamIn.writeToStdin(encodePacket({ id, isRequest: false, value }));
	  };
	  let handleRequest = async (id, request) => {
	    try {
	      if (request.command === "ping") {
	        sendResponse(id, {});
	        return;
	      }
	      if (typeof request.key === "number") {
	        const requestCallbacks = requestCallbacksByKey[request.key];
	        if (!requestCallbacks) {
	          return;
	        }
	        const callback = requestCallbacks[request.command];
	        if (callback) {
	          await callback(id, request);
	          return;
	        }
	      }
	      throw new Error(`Invalid command: ` + request.command);
	    } catch (e) {
	      const errors = [extractErrorMessageV8(e, streamIn, null, void 0, "")];
	      try {
	        sendResponse(id, { errors });
	      } catch {
	      }
	    }
	  };
	  let isFirstPacket = true;
	  let handleIncomingPacket = (bytes) => {
	    if (isFirstPacket) {
	      isFirstPacket = false;
	      let binaryVersion = String.fromCharCode(...bytes);
	      if (binaryVersion !== "0.25.9") {
	        throw new Error(`Cannot start service: Host version "${"0.25.9"}" does not match binary version ${quote(binaryVersion)}`);
	      }
	      return;
	    }
	    let packet = decodePacket(bytes);
	    if (packet.isRequest) {
	      handleRequest(packet.id, packet.value);
	    } else {
	      let callback = responseCallbacks[packet.id];
	      delete responseCallbacks[packet.id];
	      if (packet.value.error) callback(packet.value.error, {});
	      else callback(null, packet.value);
	    }
	  };
	  let buildOrContext = ({ callName, refs, options, isTTY: isTTY2, defaultWD: defaultWD2, callback }) => {
	    let refCount = 0;
	    const buildKey = nextBuildKey++;
	    const requestCallbacks = {};
	    const buildRefs = {
	      ref() {
	        if (++refCount === 1) {
	          if (refs) refs.ref();
	        }
	      },
	      unref() {
	        if (--refCount === 0) {
	          delete requestCallbacksByKey[buildKey];
	          if (refs) refs.unref();
	        }
	      }
	    };
	    requestCallbacksByKey[buildKey] = requestCallbacks;
	    buildRefs.ref();
	    buildOrContextImpl(
	      callName,
	      buildKey,
	      sendRequest,
	      sendResponse,
	      buildRefs,
	      streamIn,
	      requestCallbacks,
	      options,
	      isTTY2,
	      defaultWD2,
	      (err, res) => {
	        try {
	          callback(err, res);
	        } finally {
	          buildRefs.unref();
	        }
	      }
	    );
	  };
	  let transform2 = ({ callName, refs, input, options, isTTY: isTTY2, fs: fs3, callback }) => {
	    const details = createObjectStash();
	    let start = (inputPath) => {
	      try {
	        if (typeof input !== "string" && !(input instanceof Uint8Array))
	          throw new Error('The input to "transform" must be a string or a Uint8Array');
	        let {
	          flags,
	          mangleCache
	        } = flagsForTransformOptions(callName, options, isTTY2, transformLogLevelDefault);
	        let request = {
	          command: "transform",
	          flags,
	          inputFS: inputPath !== null,
	          input: inputPath !== null ? encodeUTF8(inputPath) : typeof input === "string" ? encodeUTF8(input) : input
	        };
	        if (mangleCache) request.mangleCache = mangleCache;
	        sendRequest(refs, request, (error, response) => {
	          if (error) return callback(new Error(error), null);
	          let errors = replaceDetailsInMessages(response.errors, details);
	          let warnings = replaceDetailsInMessages(response.warnings, details);
	          let outstanding = 1;
	          let next = () => {
	            if (--outstanding === 0) {
	              let result = {
	                warnings,
	                code: response.code,
	                map: response.map,
	                mangleCache: void 0,
	                legalComments: void 0
	              };
	              if ("legalComments" in response) result.legalComments = response == null ? void 0 : response.legalComments;
	              if (response.mangleCache) result.mangleCache = response == null ? void 0 : response.mangleCache;
	              callback(null, result);
	            }
	          };
	          if (errors.length > 0) return callback(failureErrorWithLog("Transform failed", errors, warnings), null);
	          if (response.codeFS) {
	            outstanding++;
	            fs3.readFile(response.code, (err, contents) => {
	              if (err !== null) {
	                callback(err, null);
	              } else {
	                response.code = contents;
	                next();
	              }
	            });
	          }
	          if (response.mapFS) {
	            outstanding++;
	            fs3.readFile(response.map, (err, contents) => {
	              if (err !== null) {
	                callback(err, null);
	              } else {
	                response.map = contents;
	                next();
	              }
	            });
	          }
	          next();
	        });
	      } catch (e) {
	        let flags = [];
	        try {
	          pushLogFlags(flags, options, {}, isTTY2, transformLogLevelDefault);
	        } catch {
	        }
	        const error = extractErrorMessageV8(e, streamIn, details, void 0, "");
	        sendRequest(refs, { command: "error", flags, error }, () => {
	          error.detail = details.load(error.detail);
	          callback(failureErrorWithLog("Transform failed", [error], []), null);
	        });
	      }
	    };
	    if ((typeof input === "string" || input instanceof Uint8Array) && input.length > 1024 * 1024) {
	      let next = start;
	      start = () => fs3.writeFile(input, next);
	    }
	    start(null);
	  };
	  let formatMessages2 = ({ callName, refs, messages, options, callback }) => {
	    if (!options) throw new Error(`Missing second argument in ${callName}() call`);
	    let keys = {};
	    let kind = getFlag(options, keys, "kind", mustBeString);
	    let color = getFlag(options, keys, "color", mustBeBoolean);
	    let terminalWidth = getFlag(options, keys, "terminalWidth", mustBeInteger);
	    checkForInvalidFlags(options, keys, `in ${callName}() call`);
	    if (kind === void 0) throw new Error(`Missing "kind" in ${callName}() call`);
	    if (kind !== "error" && kind !== "warning") throw new Error(`Expected "kind" to be "error" or "warning" in ${callName}() call`);
	    let request = {
	      command: "format-msgs",
	      messages: sanitizeMessages(messages, "messages", null, "", terminalWidth),
	      isWarning: kind === "warning"
	    };
	    if (color !== void 0) request.color = color;
	    if (terminalWidth !== void 0) request.terminalWidth = terminalWidth;
	    sendRequest(refs, request, (error, response) => {
	      if (error) return callback(new Error(error), null);
	      callback(null, response.messages);
	    });
	  };
	  let analyzeMetafile2 = ({ callName, refs, metafile, options, callback }) => {
	    if (options === void 0) options = {};
	    let keys = {};
	    let color = getFlag(options, keys, "color", mustBeBoolean);
	    let verbose = getFlag(options, keys, "verbose", mustBeBoolean);
	    checkForInvalidFlags(options, keys, `in ${callName}() call`);
	    let request = {
	      command: "analyze-metafile",
	      metafile
	    };
	    if (color !== void 0) request.color = color;
	    if (verbose !== void 0) request.verbose = verbose;
	    sendRequest(refs, request, (error, response) => {
	      if (error) return callback(new Error(error), null);
	      callback(null, response.result);
	    });
	  };
	  return {
	    readFromStdout,
	    afterClose,
	    service: {
	      buildOrContext,
	      transform: transform2,
	      formatMessages: formatMessages2,
	      analyzeMetafile: analyzeMetafile2
	    }
	  };
	}
	function buildOrContextImpl(callName, buildKey, sendRequest, sendResponse, refs, streamIn, requestCallbacks, options, isTTY2, defaultWD2, callback) {
	  const details = createObjectStash();
	  const isContext = callName === "context";
	  const handleError = (e, pluginName) => {
	    const flags = [];
	    try {
	      pushLogFlags(flags, options, {}, isTTY2, buildLogLevelDefault);
	    } catch {
	    }
	    const message = extractErrorMessageV8(e, streamIn, details, void 0, pluginName);
	    sendRequest(refs, { command: "error", flags, error: message }, () => {
	      message.detail = details.load(message.detail);
	      callback(failureErrorWithLog(isContext ? "Context failed" : "Build failed", [message], []), null);
	    });
	  };
	  let plugins;
	  if (typeof options === "object") {
	    const value = options.plugins;
	    if (value !== void 0) {
	      if (!Array.isArray(value)) return handleError(new Error(`"plugins" must be an array`), "");
	      plugins = value;
	    }
	  }
	  if (plugins && plugins.length > 0) {
	    if (streamIn.isSync) return handleError(new Error("Cannot use plugins in synchronous API calls"), "");
	    handlePlugins(
	      buildKey,
	      sendRequest,
	      sendResponse,
	      refs,
	      streamIn,
	      requestCallbacks,
	      options,
	      plugins,
	      details
	    ).then(
	      (result) => {
	        if (!result.ok) return handleError(result.error, result.pluginName);
	        try {
	          buildOrContextContinue(result.requestPlugins, result.runOnEndCallbacks, result.scheduleOnDisposeCallbacks);
	        } catch (e) {
	          handleError(e, "");
	        }
	      },
	      (e) => handleError(e, "")
	    );
	    return;
	  }
	  try {
	    buildOrContextContinue(null, (result, done) => done([], []), () => {
	    });
	  } catch (e) {
	    handleError(e, "");
	  }
	  function buildOrContextContinue(requestPlugins, runOnEndCallbacks, scheduleOnDisposeCallbacks) {
	    const writeDefault = streamIn.hasFS;
	    const {
	      entries,
	      flags,
	      write,
	      stdinContents,
	      stdinResolveDir,
	      absWorkingDir,
	      nodePaths,
	      mangleCache
	    } = flagsForBuildOptions(callName, options, isTTY2, buildLogLevelDefault, writeDefault);
	    if (write && !streamIn.hasFS) throw new Error(`The "write" option is unavailable in this environment`);
	    const request = {
	      command: "build",
	      key: buildKey,
	      entries,
	      flags,
	      write,
	      stdinContents,
	      stdinResolveDir,
	      absWorkingDir: absWorkingDir || defaultWD2,
	      nodePaths,
	      context: isContext
	    };
	    if (requestPlugins) request.plugins = requestPlugins;
	    if (mangleCache) request.mangleCache = mangleCache;
	    const buildResponseToResult = (response, callback2) => {
	      const result = {
	        errors: replaceDetailsInMessages(response.errors, details),
	        warnings: replaceDetailsInMessages(response.warnings, details),
	        outputFiles: void 0,
	        metafile: void 0,
	        mangleCache: void 0
	      };
	      const originalErrors = result.errors.slice();
	      const originalWarnings = result.warnings.slice();
	      if (response.outputFiles) result.outputFiles = response.outputFiles.map(convertOutputFiles);
	      if (response.metafile) result.metafile = JSON.parse(response.metafile);
	      if (response.mangleCache) result.mangleCache = response.mangleCache;
	      if (response.writeToStdout !== void 0) console.log(decodeUTF8(response.writeToStdout).replace(/\n$/, ""));
	      runOnEndCallbacks(result, (onEndErrors, onEndWarnings) => {
	        if (originalErrors.length > 0 || onEndErrors.length > 0) {
	          const error = failureErrorWithLog("Build failed", originalErrors.concat(onEndErrors), originalWarnings.concat(onEndWarnings));
	          return callback2(error, null, onEndErrors, onEndWarnings);
	        }
	        callback2(null, result, onEndErrors, onEndWarnings);
	      });
	    };
	    let latestResultPromise;
	    let provideLatestResult;
	    if (isContext)
	      requestCallbacks["on-end"] = (id, request2) => new Promise((resolve) => {
	        buildResponseToResult(request2, (err, result, onEndErrors, onEndWarnings) => {
	          const response = {
	            errors: onEndErrors,
	            warnings: onEndWarnings
	          };
	          if (provideLatestResult) provideLatestResult(err, result);
	          latestResultPromise = void 0;
	          provideLatestResult = void 0;
	          sendResponse(id, response);
	          resolve();
	        });
	      });
	    sendRequest(refs, request, (error, response) => {
	      if (error) return callback(new Error(error), null);
	      if (!isContext) {
	        return buildResponseToResult(response, (err, res) => {
	          scheduleOnDisposeCallbacks();
	          return callback(err, res);
	        });
	      }
	      if (response.errors.length > 0) {
	        return callback(failureErrorWithLog("Context failed", response.errors, response.warnings), null);
	      }
	      let didDispose = false;
	      const result = {
	        rebuild: () => {
	          if (!latestResultPromise) latestResultPromise = new Promise((resolve, reject) => {
	            let settlePromise;
	            provideLatestResult = (err, result2) => {
	              if (!settlePromise) settlePromise = () => err ? reject(err) : resolve(result2);
	            };
	            const triggerAnotherBuild = () => {
	              const request2 = {
	                command: "rebuild",
	                key: buildKey
	              };
	              sendRequest(refs, request2, (error2, response2) => {
	                if (error2) {
	                  reject(new Error(error2));
	                } else if (settlePromise) {
	                  settlePromise();
	                } else {
	                  triggerAnotherBuild();
	                }
	              });
	            };
	            triggerAnotherBuild();
	          });
	          return latestResultPromise;
	        },
	        watch: (options2 = {}) => new Promise((resolve, reject) => {
	          if (!streamIn.hasFS) throw new Error(`Cannot use the "watch" API in this environment`);
	          const keys = {};
	          const delay = getFlag(options2, keys, "delay", mustBeInteger);
	          checkForInvalidFlags(options2, keys, `in watch() call`);
	          const request2 = {
	            command: "watch",
	            key: buildKey
	          };
	          if (delay) request2.delay = delay;
	          sendRequest(refs, request2, (error2) => {
	            if (error2) reject(new Error(error2));
	            else resolve(void 0);
	          });
	        }),
	        serve: (options2 = {}) => new Promise((resolve, reject) => {
	          if (!streamIn.hasFS) throw new Error(`Cannot use the "serve" API in this environment`);
	          const keys = {};
	          const port = getFlag(options2, keys, "port", mustBeValidPortNumber);
	          const host = getFlag(options2, keys, "host", mustBeString);
	          const servedir = getFlag(options2, keys, "servedir", mustBeString);
	          const keyfile = getFlag(options2, keys, "keyfile", mustBeString);
	          const certfile = getFlag(options2, keys, "certfile", mustBeString);
	          const fallback = getFlag(options2, keys, "fallback", mustBeString);
	          const cors = getFlag(options2, keys, "cors", mustBeObject);
	          const onRequest = getFlag(options2, keys, "onRequest", mustBeFunction);
	          checkForInvalidFlags(options2, keys, `in serve() call`);
	          const request2 = {
	            command: "serve",
	            key: buildKey,
	            onRequest: !!onRequest
	          };
	          if (port !== void 0) request2.port = port;
	          if (host !== void 0) request2.host = host;
	          if (servedir !== void 0) request2.servedir = servedir;
	          if (keyfile !== void 0) request2.keyfile = keyfile;
	          if (certfile !== void 0) request2.certfile = certfile;
	          if (fallback !== void 0) request2.fallback = fallback;
	          if (cors) {
	            const corsKeys = {};
	            const origin = getFlag(cors, corsKeys, "origin", mustBeStringOrArrayOfStrings);
	            checkForInvalidFlags(cors, corsKeys, `on "cors" object`);
	            if (Array.isArray(origin)) request2.corsOrigin = origin;
	            else if (origin !== void 0) request2.corsOrigin = [origin];
	          }
	          sendRequest(refs, request2, (error2, response2) => {
	            if (error2) return reject(new Error(error2));
	            if (onRequest) {
	              requestCallbacks["serve-request"] = (id, request3) => {
	                onRequest(request3.args);
	                sendResponse(id, {});
	              };
	            }
	            resolve(response2);
	          });
	        }),
	        cancel: () => new Promise((resolve) => {
	          if (didDispose) return resolve();
	          const request2 = {
	            command: "cancel",
	            key: buildKey
	          };
	          sendRequest(refs, request2, () => {
	            resolve();
	          });
	        }),
	        dispose: () => new Promise((resolve) => {
	          if (didDispose) return resolve();
	          didDispose = true;
	          const request2 = {
	            command: "dispose",
	            key: buildKey
	          };
	          sendRequest(refs, request2, () => {
	            resolve();
	            scheduleOnDisposeCallbacks();
	            refs.unref();
	          });
	        })
	      };
	      refs.ref();
	      callback(null, result);
	    });
	  }
	}
	var handlePlugins = async (buildKey, sendRequest, sendResponse, refs, streamIn, requestCallbacks, initialOptions, plugins, details) => {
	  let onStartCallbacks = [];
	  let onEndCallbacks = [];
	  let onResolveCallbacks = {};
	  let onLoadCallbacks = {};
	  let onDisposeCallbacks = [];
	  let nextCallbackID = 0;
	  let i = 0;
	  let requestPlugins = [];
	  let isSetupDone = false;
	  plugins = [...plugins];
	  for (let item of plugins) {
	    let keys = {};
	    if (typeof item !== "object") throw new Error(`Plugin at index ${i} must be an object`);
	    const name = getFlag(item, keys, "name", mustBeString);
	    if (typeof name !== "string" || name === "") throw new Error(`Plugin at index ${i} is missing a name`);
	    try {
	      let setup = getFlag(item, keys, "setup", mustBeFunction);
	      if (typeof setup !== "function") throw new Error(`Plugin is missing a setup function`);
	      checkForInvalidFlags(item, keys, `on plugin ${quote(name)}`);
	      let plugin = {
	        name,
	        onStart: false,
	        onEnd: false,
	        onResolve: [],
	        onLoad: []
	      };
	      i++;
	      let resolve = (path3, options = {}) => {
	        if (!isSetupDone) throw new Error('Cannot call "resolve" before plugin setup has completed');
	        if (typeof path3 !== "string") throw new Error(`The path to resolve must be a string`);
	        let keys2 = /* @__PURE__ */ Object.create(null);
	        let pluginName = getFlag(options, keys2, "pluginName", mustBeString);
	        let importer = getFlag(options, keys2, "importer", mustBeString);
	        let namespace = getFlag(options, keys2, "namespace", mustBeString);
	        let resolveDir = getFlag(options, keys2, "resolveDir", mustBeString);
	        let kind = getFlag(options, keys2, "kind", mustBeString);
	        let pluginData = getFlag(options, keys2, "pluginData", canBeAnything);
	        let importAttributes = getFlag(options, keys2, "with", mustBeObject);
	        checkForInvalidFlags(options, keys2, "in resolve() call");
	        return new Promise((resolve2, reject) => {
	          const request = {
	            command: "resolve",
	            path: path3,
	            key: buildKey,
	            pluginName: name
	          };
	          if (pluginName != null) request.pluginName = pluginName;
	          if (importer != null) request.importer = importer;
	          if (namespace != null) request.namespace = namespace;
	          if (resolveDir != null) request.resolveDir = resolveDir;
	          if (kind != null) request.kind = kind;
	          else throw new Error(`Must specify "kind" when calling "resolve"`);
	          if (pluginData != null) request.pluginData = details.store(pluginData);
	          if (importAttributes != null) request.with = sanitizeStringMap(importAttributes, "with");
	          sendRequest(refs, request, (error, response) => {
	            if (error !== null) reject(new Error(error));
	            else resolve2({
	              errors: replaceDetailsInMessages(response.errors, details),
	              warnings: replaceDetailsInMessages(response.warnings, details),
	              path: response.path,
	              external: response.external,
	              sideEffects: response.sideEffects,
	              namespace: response.namespace,
	              suffix: response.suffix,
	              pluginData: details.load(response.pluginData)
	            });
	          });
	        });
	      };
	      let promise = setup({
	        initialOptions,
	        resolve,
	        onStart(callback) {
	          let registeredText = `This error came from the "onStart" callback registered here:`;
	          let registeredNote = extractCallerV8(new Error(registeredText), streamIn, "onStart");
	          onStartCallbacks.push({ name, callback, note: registeredNote });
	          plugin.onStart = true;
	        },
	        onEnd(callback) {
	          let registeredText = `This error came from the "onEnd" callback registered here:`;
	          let registeredNote = extractCallerV8(new Error(registeredText), streamIn, "onEnd");
	          onEndCallbacks.push({ name, callback, note: registeredNote });
	          plugin.onEnd = true;
	        },
	        onResolve(options, callback) {
	          let registeredText = `This error came from the "onResolve" callback registered here:`;
	          let registeredNote = extractCallerV8(new Error(registeredText), streamIn, "onResolve");
	          let keys2 = {};
	          let filter = getFlag(options, keys2, "filter", mustBeRegExp);
	          let namespace = getFlag(options, keys2, "namespace", mustBeString);
	          checkForInvalidFlags(options, keys2, `in onResolve() call for plugin ${quote(name)}`);
	          if (filter == null) throw new Error(`onResolve() call is missing a filter`);
	          let id = nextCallbackID++;
	          onResolveCallbacks[id] = { name, callback, note: registeredNote };
	          plugin.onResolve.push({ id, filter: jsRegExpToGoRegExp(filter), namespace: namespace || "" });
	        },
	        onLoad(options, callback) {
	          let registeredText = `This error came from the "onLoad" callback registered here:`;
	          let registeredNote = extractCallerV8(new Error(registeredText), streamIn, "onLoad");
	          let keys2 = {};
	          let filter = getFlag(options, keys2, "filter", mustBeRegExp);
	          let namespace = getFlag(options, keys2, "namespace", mustBeString);
	          checkForInvalidFlags(options, keys2, `in onLoad() call for plugin ${quote(name)}`);
	          if (filter == null) throw new Error(`onLoad() call is missing a filter`);
	          let id = nextCallbackID++;
	          onLoadCallbacks[id] = { name, callback, note: registeredNote };
	          plugin.onLoad.push({ id, filter: jsRegExpToGoRegExp(filter), namespace: namespace || "" });
	        },
	        onDispose(callback) {
	          onDisposeCallbacks.push(callback);
	        },
	        esbuild: streamIn.esbuild
	      });
	      if (promise) await promise;
	      requestPlugins.push(plugin);
	    } catch (e) {
	      return { ok: false, error: e, pluginName: name };
	    }
	  }
	  requestCallbacks["on-start"] = async (id, request) => {
	    details.clear();
	    let response = { errors: [], warnings: [] };
	    await Promise.all(onStartCallbacks.map(async ({ name, callback, note }) => {
	      try {
	        let result = await callback();
	        if (result != null) {
	          if (typeof result !== "object") throw new Error(`Expected onStart() callback in plugin ${quote(name)} to return an object`);
	          let keys = {};
	          let errors = getFlag(result, keys, "errors", mustBeArray);
	          let warnings = getFlag(result, keys, "warnings", mustBeArray);
	          checkForInvalidFlags(result, keys, `from onStart() callback in plugin ${quote(name)}`);
	          if (errors != null) response.errors.push(...sanitizeMessages(errors, "errors", details, name, void 0));
	          if (warnings != null) response.warnings.push(...sanitizeMessages(warnings, "warnings", details, name, void 0));
	        }
	      } catch (e) {
	        response.errors.push(extractErrorMessageV8(e, streamIn, details, note && note(), name));
	      }
	    }));
	    sendResponse(id, response);
	  };
	  requestCallbacks["on-resolve"] = async (id, request) => {
	    let response = {}, name = "", callback, note;
	    for (let id2 of request.ids) {
	      try {
	        ({ name, callback, note } = onResolveCallbacks[id2]);
	        let result = await callback({
	          path: request.path,
	          importer: request.importer,
	          namespace: request.namespace,
	          resolveDir: request.resolveDir,
	          kind: request.kind,
	          pluginData: details.load(request.pluginData),
	          with: request.with
	        });
	        if (result != null) {
	          if (typeof result !== "object") throw new Error(`Expected onResolve() callback in plugin ${quote(name)} to return an object`);
	          let keys = {};
	          let pluginName = getFlag(result, keys, "pluginName", mustBeString);
	          let path3 = getFlag(result, keys, "path", mustBeString);
	          let namespace = getFlag(result, keys, "namespace", mustBeString);
	          let suffix = getFlag(result, keys, "suffix", mustBeString);
	          let external = getFlag(result, keys, "external", mustBeBoolean);
	          let sideEffects = getFlag(result, keys, "sideEffects", mustBeBoolean);
	          let pluginData = getFlag(result, keys, "pluginData", canBeAnything);
	          let errors = getFlag(result, keys, "errors", mustBeArray);
	          let warnings = getFlag(result, keys, "warnings", mustBeArray);
	          let watchFiles = getFlag(result, keys, "watchFiles", mustBeArrayOfStrings);
	          let watchDirs = getFlag(result, keys, "watchDirs", mustBeArrayOfStrings);
	          checkForInvalidFlags(result, keys, `from onResolve() callback in plugin ${quote(name)}`);
	          response.id = id2;
	          if (pluginName != null) response.pluginName = pluginName;
	          if (path3 != null) response.path = path3;
	          if (namespace != null) response.namespace = namespace;
	          if (suffix != null) response.suffix = suffix;
	          if (external != null) response.external = external;
	          if (sideEffects != null) response.sideEffects = sideEffects;
	          if (pluginData != null) response.pluginData = details.store(pluginData);
	          if (errors != null) response.errors = sanitizeMessages(errors, "errors", details, name, void 0);
	          if (warnings != null) response.warnings = sanitizeMessages(warnings, "warnings", details, name, void 0);
	          if (watchFiles != null) response.watchFiles = sanitizeStringArray(watchFiles, "watchFiles");
	          if (watchDirs != null) response.watchDirs = sanitizeStringArray(watchDirs, "watchDirs");
	          break;
	        }
	      } catch (e) {
	        response = { id: id2, errors: [extractErrorMessageV8(e, streamIn, details, note && note(), name)] };
	        break;
	      }
	    }
	    sendResponse(id, response);
	  };
	  requestCallbacks["on-load"] = async (id, request) => {
	    let response = {}, name = "", callback, note;
	    for (let id2 of request.ids) {
	      try {
	        ({ name, callback, note } = onLoadCallbacks[id2]);
	        let result = await callback({
	          path: request.path,
	          namespace: request.namespace,
	          suffix: request.suffix,
	          pluginData: details.load(request.pluginData),
	          with: request.with
	        });
	        if (result != null) {
	          if (typeof result !== "object") throw new Error(`Expected onLoad() callback in plugin ${quote(name)} to return an object`);
	          let keys = {};
	          let pluginName = getFlag(result, keys, "pluginName", mustBeString);
	          let contents = getFlag(result, keys, "contents", mustBeStringOrUint8Array);
	          let resolveDir = getFlag(result, keys, "resolveDir", mustBeString);
	          let pluginData = getFlag(result, keys, "pluginData", canBeAnything);
	          let loader = getFlag(result, keys, "loader", mustBeString);
	          let errors = getFlag(result, keys, "errors", mustBeArray);
	          let warnings = getFlag(result, keys, "warnings", mustBeArray);
	          let watchFiles = getFlag(result, keys, "watchFiles", mustBeArrayOfStrings);
	          let watchDirs = getFlag(result, keys, "watchDirs", mustBeArrayOfStrings);
	          checkForInvalidFlags(result, keys, `from onLoad() callback in plugin ${quote(name)}`);
	          response.id = id2;
	          if (pluginName != null) response.pluginName = pluginName;
	          if (contents instanceof Uint8Array) response.contents = contents;
	          else if (contents != null) response.contents = encodeUTF8(contents);
	          if (resolveDir != null) response.resolveDir = resolveDir;
	          if (pluginData != null) response.pluginData = details.store(pluginData);
	          if (loader != null) response.loader = loader;
	          if (errors != null) response.errors = sanitizeMessages(errors, "errors", details, name, void 0);
	          if (warnings != null) response.warnings = sanitizeMessages(warnings, "warnings", details, name, void 0);
	          if (watchFiles != null) response.watchFiles = sanitizeStringArray(watchFiles, "watchFiles");
	          if (watchDirs != null) response.watchDirs = sanitizeStringArray(watchDirs, "watchDirs");
	          break;
	        }
	      } catch (e) {
	        response = { id: id2, errors: [extractErrorMessageV8(e, streamIn, details, note && note(), name)] };
	        break;
	      }
	    }
	    sendResponse(id, response);
	  };
	  let runOnEndCallbacks = (result, done) => done([], []);
	  if (onEndCallbacks.length > 0) {
	    runOnEndCallbacks = (result, done) => {
	      (async () => {
	        const onEndErrors = [];
	        const onEndWarnings = [];
	        for (const { name, callback, note } of onEndCallbacks) {
	          let newErrors;
	          let newWarnings;
	          try {
	            const value = await callback(result);
	            if (value != null) {
	              if (typeof value !== "object") throw new Error(`Expected onEnd() callback in plugin ${quote(name)} to return an object`);
	              let keys = {};
	              let errors = getFlag(value, keys, "errors", mustBeArray);
	              let warnings = getFlag(value, keys, "warnings", mustBeArray);
	              checkForInvalidFlags(value, keys, `from onEnd() callback in plugin ${quote(name)}`);
	              if (errors != null) newErrors = sanitizeMessages(errors, "errors", details, name, void 0);
	              if (warnings != null) newWarnings = sanitizeMessages(warnings, "warnings", details, name, void 0);
	            }
	          } catch (e) {
	            newErrors = [extractErrorMessageV8(e, streamIn, details, note && note(), name)];
	          }
	          if (newErrors) {
	            onEndErrors.push(...newErrors);
	            try {
	              result.errors.push(...newErrors);
	            } catch {
	            }
	          }
	          if (newWarnings) {
	            onEndWarnings.push(...newWarnings);
	            try {
	              result.warnings.push(...newWarnings);
	            } catch {
	            }
	          }
	        }
	        done(onEndErrors, onEndWarnings);
	      })();
	    };
	  }
	  let scheduleOnDisposeCallbacks = () => {
	    for (const cb of onDisposeCallbacks) {
	      setTimeout(() => cb(), 0);
	    }
	  };
	  isSetupDone = true;
	  return {
	    ok: true,
	    requestPlugins,
	    runOnEndCallbacks,
	    scheduleOnDisposeCallbacks
	  };
	};
	function createObjectStash() {
	  const map = /* @__PURE__ */ new Map();
	  let nextID = 0;
	  return {
	    clear() {
	      map.clear();
	    },
	    load(id) {
	      return map.get(id);
	    },
	    store(value) {
	      if (value === void 0) return -1;
	      const id = nextID++;
	      map.set(id, value);
	      return id;
	    }
	  };
	}
	function extractCallerV8(e, streamIn, ident) {
	  let note;
	  let tried = false;
	  return () => {
	    if (tried) return note;
	    tried = true;
	    try {
	      let lines = (e.stack + "").split("\n");
	      lines.splice(1, 1);
	      let location = parseStackLinesV8(streamIn, lines, ident);
	      if (location) {
	        note = { text: e.message, location };
	        return note;
	      }
	    } catch {
	    }
	  };
	}
	function extractErrorMessageV8(e, streamIn, stash, note, pluginName) {
	  let text = "Internal error";
	  let location = null;
	  try {
	    text = (e && e.message || e) + "";
	  } catch {
	  }
	  try {
	    location = parseStackLinesV8(streamIn, (e.stack + "").split("\n"), "");
	  } catch {
	  }
	  return { id: "", pluginName, text, location, notes: note ? [note] : [], detail: stash ? stash.store(e) : -1 };
	}
	function parseStackLinesV8(streamIn, lines, ident) {
	  let at = "    at ";
	  if (streamIn.readFileSync && !lines[0].startsWith(at) && lines[1].startsWith(at)) {
	    for (let i = 1; i < lines.length; i++) {
	      let line = lines[i];
	      if (!line.startsWith(at)) continue;
	      line = line.slice(at.length);
	      while (true) {
	        let match = /^(?:new |async )?\S+ \((.*)\)$/.exec(line);
	        if (match) {
	          line = match[1];
	          continue;
	        }
	        match = /^eval at \S+ \((.*)\)(?:, \S+:\d+:\d+)?$/.exec(line);
	        if (match) {
	          line = match[1];
	          continue;
	        }
	        match = /^(\S+):(\d+):(\d+)$/.exec(line);
	        if (match) {
	          let contents;
	          try {
	            contents = streamIn.readFileSync(match[1], "utf8");
	          } catch {
	            break;
	          }
	          let lineText = contents.split(/\r\n|\r|\n|\u2028|\u2029/)[+match[2] - 1] || "";
	          let column = +match[3] - 1;
	          let length = lineText.slice(column, column + ident.length) === ident ? ident.length : 0;
	          return {
	            file: match[1],
	            namespace: "file",
	            line: +match[2],
	            column: encodeUTF8(lineText.slice(0, column)).length,
	            length: encodeUTF8(lineText.slice(column, column + length)).length,
	            lineText: lineText + "\n" + lines.slice(1).join("\n"),
	            suggestion: ""
	          };
	        }
	        break;
	      }
	    }
	  }
	  return null;
	}
	function failureErrorWithLog(text, errors, warnings) {
	  let limit = 5;
	  text += errors.length < 1 ? "" : ` with ${errors.length} error${errors.length < 2 ? "" : "s"}:` + errors.slice(0, limit + 1).map((e, i) => {
	    if (i === limit) return "\n...";
	    if (!e.location) return `
error: ${e.text}`;
	    let { file, line, column } = e.location;
	    let pluginText = e.pluginName ? `[plugin: ${e.pluginName}] ` : "";
	    return `
${file}:${line}:${column}: ERROR: ${pluginText}${e.text}`;
	  }).join("");
	  let error = new Error(text);
	  for (const [key, value] of [["errors", errors], ["warnings", warnings]]) {
	    Object.defineProperty(error, key, {
	      configurable: true,
	      enumerable: true,
	      get: () => value,
	      set: (value2) => Object.defineProperty(error, key, {
	        configurable: true,
	        enumerable: true,
	        value: value2
	      })
	    });
	  }
	  return error;
	}
	function replaceDetailsInMessages(messages, stash) {
	  for (const message of messages) {
	    message.detail = stash.load(message.detail);
	  }
	  return messages;
	}
	function sanitizeLocation(location, where, terminalWidth) {
	  if (location == null) return null;
	  let keys = {};
	  let file = getFlag(location, keys, "file", mustBeString);
	  let namespace = getFlag(location, keys, "namespace", mustBeString);
	  let line = getFlag(location, keys, "line", mustBeInteger);
	  let column = getFlag(location, keys, "column", mustBeInteger);
	  let length = getFlag(location, keys, "length", mustBeInteger);
	  let lineText = getFlag(location, keys, "lineText", mustBeString);
	  let suggestion = getFlag(location, keys, "suggestion", mustBeString);
	  checkForInvalidFlags(location, keys, where);
	  if (lineText) {
	    const relevantASCII = lineText.slice(
	      0,
	      (column && column > 0 ? column : 0) + (length && length > 0 ? length : 0) + (terminalWidth && terminalWidth > 0 ? terminalWidth : 80)
	    );
	    if (!/[\x7F-\uFFFF]/.test(relevantASCII) && !/\n/.test(lineText)) {
	      lineText = relevantASCII;
	    }
	  }
	  return {
	    file: file || "",
	    namespace: namespace || "",
	    line: line || 0,
	    column: column || 0,
	    length: length || 0,
	    lineText: lineText || "",
	    suggestion: suggestion || ""
	  };
	}
	function sanitizeMessages(messages, property, stash, fallbackPluginName, terminalWidth) {
	  let messagesClone = [];
	  let index = 0;
	  for (const message of messages) {
	    let keys = {};
	    let id = getFlag(message, keys, "id", mustBeString);
	    let pluginName = getFlag(message, keys, "pluginName", mustBeString);
	    let text = getFlag(message, keys, "text", mustBeString);
	    let location = getFlag(message, keys, "location", mustBeObjectOrNull);
	    let notes = getFlag(message, keys, "notes", mustBeArray);
	    let detail = getFlag(message, keys, "detail", canBeAnything);
	    let where = `in element ${index} of "${property}"`;
	    checkForInvalidFlags(message, keys, where);
	    let notesClone = [];
	    if (notes) {
	      for (const note of notes) {
	        let noteKeys = {};
	        let noteText = getFlag(note, noteKeys, "text", mustBeString);
	        let noteLocation = getFlag(note, noteKeys, "location", mustBeObjectOrNull);
	        checkForInvalidFlags(note, noteKeys, where);
	        notesClone.push({
	          text: noteText || "",
	          location: sanitizeLocation(noteLocation, where, terminalWidth)
	        });
	      }
	    }
	    messagesClone.push({
	      id: id || "",
	      pluginName: pluginName || fallbackPluginName,
	      text: text || "",
	      location: sanitizeLocation(location, where, terminalWidth),
	      notes: notesClone,
	      detail: stash ? stash.store(detail) : -1
	    });
	    index++;
	  }
	  return messagesClone;
	}
	function sanitizeStringArray(values, property) {
	  const result = [];
	  for (const value of values) {
	    if (typeof value !== "string") throw new Error(`${quote(property)} must be an array of strings`);
	    result.push(value);
	  }
	  return result;
	}
	function sanitizeStringMap(map, property) {
	  const result = /* @__PURE__ */ Object.create(null);
	  for (const key in map) {
	    const value = map[key];
	    if (typeof value !== "string") throw new Error(`key ${quote(key)} in object ${quote(property)} must be a string`);
	    result[key] = value;
	  }
	  return result;
	}
	function convertOutputFiles({ path: path3, contents, hash }) {
	  let text = null;
	  return {
	    path: path3,
	    contents,
	    hash,
	    get text() {
	      const binary = this.contents;
	      if (text === null || binary !== contents) {
	        contents = binary;
	        text = decodeUTF8(binary);
	      }
	      return text;
	    }
	  };
	}
	function jsRegExpToGoRegExp(regexp) {
	  let result = regexp.source;
	  if (regexp.flags) result = `(?${regexp.flags})${result}`;
	  return result;
	}

	// lib/npm/node-platform.ts
	var fs = require$$0;
	var os = require$$1;
	var path = require$$0$1;
	var ESBUILD_BINARY_PATH = process.env.ESBUILD_BINARY_PATH || ESBUILD_BINARY_PATH;
	var isValidBinaryPath = (x) => !!x && x !== "/usr/bin/esbuild";
	var packageDarwin_arm64 = "@esbuild/darwin-arm64";
	var packageDarwin_x64 = "@esbuild/darwin-x64";
	var knownWindowsPackages = {
	  "win32 arm64 LE": "@esbuild/win32-arm64",
	  "win32 ia32 LE": "@esbuild/win32-ia32",
	  "win32 x64 LE": "@esbuild/win32-x64"
	};
	var knownUnixlikePackages = {
	  "aix ppc64 BE": "@esbuild/aix-ppc64",
	  "android arm64 LE": "@esbuild/android-arm64",
	  "darwin arm64 LE": "@esbuild/darwin-arm64",
	  "darwin x64 LE": "@esbuild/darwin-x64",
	  "freebsd arm64 LE": "@esbuild/freebsd-arm64",
	  "freebsd x64 LE": "@esbuild/freebsd-x64",
	  "linux arm LE": "@esbuild/linux-arm",
	  "linux arm64 LE": "@esbuild/linux-arm64",
	  "linux ia32 LE": "@esbuild/linux-ia32",
	  "linux mips64el LE": "@esbuild/linux-mips64el",
	  "linux ppc64 LE": "@esbuild/linux-ppc64",
	  "linux riscv64 LE": "@esbuild/linux-riscv64",
	  "linux s390x BE": "@esbuild/linux-s390x",
	  "linux x64 LE": "@esbuild/linux-x64",
	  "linux loong64 LE": "@esbuild/linux-loong64",
	  "netbsd arm64 LE": "@esbuild/netbsd-arm64",
	  "netbsd x64 LE": "@esbuild/netbsd-x64",
	  "openbsd arm64 LE": "@esbuild/openbsd-arm64",
	  "openbsd x64 LE": "@esbuild/openbsd-x64",
	  "sunos x64 LE": "@esbuild/sunos-x64"
	};
	var knownWebAssemblyFallbackPackages = {
	  "android arm LE": "@esbuild/android-arm",
	  "android x64 LE": "@esbuild/android-x64",
	  "openharmony arm64 LE": "@esbuild/openharmony-arm64"
	};
	function pkgAndSubpathForCurrentPlatform() {
	  let pkg;
	  let subpath;
	  let isWASM = false;
	  let platformKey = `${process.platform} ${os.arch()} ${os.endianness()}`;
	  if (platformKey in knownWindowsPackages) {
	    pkg = knownWindowsPackages[platformKey];
	    subpath = "esbuild.exe";
	  } else if (platformKey in knownUnixlikePackages) {
	    pkg = knownUnixlikePackages[platformKey];
	    subpath = "bin/esbuild";
	  } else if (platformKey in knownWebAssemblyFallbackPackages) {
	    pkg = knownWebAssemblyFallbackPackages[platformKey];
	    subpath = "bin/esbuild";
	    isWASM = true;
	  } else {
	    throw new Error(`Unsupported platform: ${platformKey}`);
	  }
	  return { pkg, subpath, isWASM };
	}
	function pkgForSomeOtherPlatform() {
	  const libMainJS = require.resolve("esbuild");
	  const nodeModulesDirectory = path.dirname(path.dirname(path.dirname(libMainJS)));
	  if (path.basename(nodeModulesDirectory) === "node_modules") {
	    for (const unixKey in knownUnixlikePackages) {
	      try {
	        const pkg = knownUnixlikePackages[unixKey];
	        if (fs.existsSync(path.join(nodeModulesDirectory, pkg))) return pkg;
	      } catch {
	      }
	    }
	    for (const windowsKey in knownWindowsPackages) {
	      try {
	        const pkg = knownWindowsPackages[windowsKey];
	        if (fs.existsSync(path.join(nodeModulesDirectory, pkg))) return pkg;
	      } catch {
	      }
	    }
	  }
	  return null;
	}
	function downloadedBinPath(pkg, subpath) {
	  const esbuildLibDir = path.dirname(require.resolve("esbuild"));
	  return path.join(esbuildLibDir, `downloaded-${pkg.replace("/", "-")}-${path.basename(subpath)}`);
	}
	function generateBinPath() {
	  if (isValidBinaryPath(ESBUILD_BINARY_PATH)) {
	    if (!fs.existsSync(ESBUILD_BINARY_PATH)) {
	      console.warn(`[esbuild] Ignoring bad configuration: ESBUILD_BINARY_PATH=${ESBUILD_BINARY_PATH}`);
	    } else {
	      return { binPath: ESBUILD_BINARY_PATH, isWASM: false };
	    }
	  }
	  const { pkg, subpath, isWASM } = pkgAndSubpathForCurrentPlatform();
	  let binPath;
	  try {
	    binPath = require.resolve(`${pkg}/${subpath}`);
	  } catch (e) {
	    binPath = downloadedBinPath(pkg, subpath);
	    if (!fs.existsSync(binPath)) {
	      try {
	        require.resolve(pkg);
	      } catch {
	        const otherPkg = pkgForSomeOtherPlatform();
	        if (otherPkg) {
	          let suggestions = `
Specifically the "${otherPkg}" package is present but this platform
needs the "${pkg}" package instead. People often get into this
situation by installing esbuild on Windows or macOS and copying "node_modules"
into a Docker image that runs Linux, or by copying "node_modules" between
Windows and WSL environments.

If you are installing with npm, you can try not copying the "node_modules"
directory when you copy the files over, and running "npm ci" or "npm install"
on the destination platform after the copy. Or you could consider using yarn
instead of npm which has built-in support for installing a package on multiple
platforms simultaneously.

If you are installing with yarn, you can try listing both this platform and the
other platform in your ".yarnrc.yml" file using the "supportedArchitectures"
feature: https://yarnpkg.com/configuration/yarnrc/#supportedArchitectures
Keep in mind that this means multiple copies of esbuild will be present.
`;
	          if (pkg === packageDarwin_x64 && otherPkg === packageDarwin_arm64 || pkg === packageDarwin_arm64 && otherPkg === packageDarwin_x64) {
	            suggestions = `
Specifically the "${otherPkg}" package is present but this platform
needs the "${pkg}" package instead. People often get into this
situation by installing esbuild with npm running inside of Rosetta 2 and then
trying to use it with node running outside of Rosetta 2, or vice versa (Rosetta
2 is Apple's on-the-fly x86_64-to-arm64 translation service).

If you are installing with npm, you can try ensuring that both npm and node are
not running under Rosetta 2 and then reinstalling esbuild. This likely involves
changing how you installed npm and/or node. For example, installing node with
the universal installer here should work: https://nodejs.org/en/download/. Or
you could consider using yarn instead of npm which has built-in support for
installing a package on multiple platforms simultaneously.

If you are installing with yarn, you can try listing both "arm64" and "x64"
in your ".yarnrc.yml" file using the "supportedArchitectures" feature:
https://yarnpkg.com/configuration/yarnrc/#supportedArchitectures
Keep in mind that this means multiple copies of esbuild will be present.
`;
	          }
	          throw new Error(`
You installed esbuild for another platform than the one you're currently using.
This won't work because esbuild is written with native code and needs to
install a platform-specific binary executable.
${suggestions}
Another alternative is to use the "esbuild-wasm" package instead, which works
the same way on all platforms. But it comes with a heavy performance cost and
can sometimes be 10x slower than the "esbuild" package, so you may also not
want to do that.
`);
	        }
	        throw new Error(`The package "${pkg}" could not be found, and is needed by esbuild.

If you are installing esbuild with npm, make sure that you don't specify the
"--no-optional" or "--omit=optional" flags. The "optionalDependencies" feature
of "package.json" is used by esbuild to install the correct binary executable
for your current platform.`);
	      }
	      throw e;
	    }
	  }
	  if (/\.zip\//.test(binPath)) {
	    let pnpapi;
	    try {
	      pnpapi = require("pnpapi");
	    } catch (e) {
	    }
	    if (pnpapi) {
	      const root = pnpapi.getPackageInformation(pnpapi.topLevel).packageLocation;
	      const binTargetPath = path.join(
	        root,
	        "node_modules",
	        ".cache",
	        "esbuild",
	        `pnpapi-${pkg.replace("/", "-")}-${"0.25.9"}-${path.basename(subpath)}`
	      );
	      if (!fs.existsSync(binTargetPath)) {
	        fs.mkdirSync(path.dirname(binTargetPath), { recursive: true });
	        fs.copyFileSync(binPath, binTargetPath);
	        fs.chmodSync(binTargetPath, 493);
	      }
	      return { binPath: binTargetPath, isWASM };
	    }
	  }
	  return { binPath, isWASM };
	}

	// lib/npm/node.ts
	var child_process = require$$4;
	var crypto = require$$5;
	var path2 = require$$0$1;
	var fs2 = require$$0;
	var os2 = require$$1;
	var tty = require$$6;
	var worker_threads;
	if (process.env.ESBUILD_WORKER_THREADS !== "0") {
	  try {
	    worker_threads = require("worker_threads");
	  } catch {
	  }
	  let [major, minor] = process.versions.node.split(".");
	  if (
	    // <v12.17.0 does not work
	    +major < 12 || +major === 12 && +minor < 17 || +major === 13 && +minor < 13
	  ) {
	    worker_threads = void 0;
	  }
	}
	var _a;
	var isInternalWorkerThread = ((_a = worker_threads == null ? void 0 : worker_threads.workerData) == null ? void 0 : _a.esbuildVersion) === "0.25.9";
	var esbuildCommandAndArgs = () => {
	  if ((!ESBUILD_BINARY_PATH || false) && (path2.basename(__filename) !== "main.js" || path2.basename(__dirname) !== "lib")) {
	    throw new Error(
	      `The esbuild JavaScript API cannot be bundled. Please mark the "esbuild" package as external so it's not included in the bundle.

	More information: The file containing the code for esbuild's JavaScript API (${__filename}) does not appear to be inside the esbuild package on the file system, which usually means that the esbuild package was bundled into another file. This is problematic because the API needs to run a binary executable inside the esbuild package which is located using a relative path from the API code to the executable. If the esbuild package is bundled, the relative path will be incorrect and the executable won't be found.`
	    );
	  }
	  {
	    const { binPath, isWASM } = generateBinPath();
	    if (isWASM) {
	      return ["node", [binPath]];
	    } else {
	      return [binPath, []];
	    }
	  }
	};
	var isTTY = () => tty.isatty(2);
	var fsSync = {
	  readFile(tempFile, callback) {
	    try {
	      let contents = fs2.readFileSync(tempFile, "utf8");
	      try {
	        fs2.unlinkSync(tempFile);
	      } catch {
	      }
	      callback(null, contents);
	    } catch (err) {
	      callback(err, null);
	    }
	  },
	  writeFile(contents, callback) {
	    try {
	      let tempFile = randomFileName();
	      fs2.writeFileSync(tempFile, contents);
	      callback(tempFile);
	    } catch {
	      callback(null);
	    }
	  }
	};
	var fsAsync = {
	  readFile(tempFile, callback) {
	    try {
	      fs2.readFile(tempFile, "utf8", (err, contents) => {
	        try {
	          fs2.unlink(tempFile, () => callback(err, contents));
	        } catch {
	          callback(err, contents);
	        }
	      });
	    } catch (err) {
	      callback(err, null);
	    }
	  },
	  writeFile(contents, callback) {
	    try {
	      let tempFile = randomFileName();
	      fs2.writeFile(tempFile, contents, (err) => err !== null ? callback(null) : callback(tempFile));
	    } catch {
	      callback(null);
	    }
	  }
	};
	var version = "0.25.9";
	var build = (options) => ensureServiceIsRunning().build(options);
	var context = (buildOptions) => ensureServiceIsRunning().context(buildOptions);
	var transform = (input, options) => ensureServiceIsRunning().transform(input, options);
	var formatMessages = (messages, options) => ensureServiceIsRunning().formatMessages(messages, options);
	var analyzeMetafile = (messages, options) => ensureServiceIsRunning().analyzeMetafile(messages, options);
	var buildSync = (options) => {
	  if (worker_threads && !isInternalWorkerThread) {
	    if (!workerThreadService) workerThreadService = startWorkerThreadService(worker_threads);
	    return workerThreadService.buildSync(options);
	  }
	  let result;
	  runServiceSync((service) => service.buildOrContext({
	    callName: "buildSync",
	    refs: null,
	    options,
	    isTTY: isTTY(),
	    defaultWD,
	    callback: (err, res) => {
	      if (err) throw err;
	      result = res;
	    }
	  }));
	  return result;
	};
	var transformSync = (input, options) => {
	  if (worker_threads && !isInternalWorkerThread) {
	    if (!workerThreadService) workerThreadService = startWorkerThreadService(worker_threads);
	    return workerThreadService.transformSync(input, options);
	  }
	  let result;
	  runServiceSync((service) => service.transform({
	    callName: "transformSync",
	    refs: null,
	    input,
	    options: options || {},
	    isTTY: isTTY(),
	    fs: fsSync,
	    callback: (err, res) => {
	      if (err) throw err;
	      result = res;
	    }
	  }));
	  return result;
	};
	var formatMessagesSync = (messages, options) => {
	  if (worker_threads && !isInternalWorkerThread) {
	    if (!workerThreadService) workerThreadService = startWorkerThreadService(worker_threads);
	    return workerThreadService.formatMessagesSync(messages, options);
	  }
	  let result;
	  runServiceSync((service) => service.formatMessages({
	    callName: "formatMessagesSync",
	    refs: null,
	    messages,
	    options,
	    callback: (err, res) => {
	      if (err) throw err;
	      result = res;
	    }
	  }));
	  return result;
	};
	var analyzeMetafileSync = (metafile, options) => {
	  if (worker_threads && !isInternalWorkerThread) {
	    if (!workerThreadService) workerThreadService = startWorkerThreadService(worker_threads);
	    return workerThreadService.analyzeMetafileSync(metafile, options);
	  }
	  let result;
	  runServiceSync((service) => service.analyzeMetafile({
	    callName: "analyzeMetafileSync",
	    refs: null,
	    metafile: typeof metafile === "string" ? metafile : JSON.stringify(metafile),
	    options,
	    callback: (err, res) => {
	      if (err) throw err;
	      result = res;
	    }
	  }));
	  return result;
	};
	var stop = () => {
	  if (stopService) stopService();
	  if (workerThreadService) workerThreadService.stop();
	  return Promise.resolve();
	};
	var initializeWasCalled = false;
	var initialize = (options) => {
	  options = validateInitializeOptions(options || {});
	  if (options.wasmURL) throw new Error(`The "wasmURL" option only works in the browser`);
	  if (options.wasmModule) throw new Error(`The "wasmModule" option only works in the browser`);
	  if (options.worker) throw new Error(`The "worker" option only works in the browser`);
	  if (initializeWasCalled) throw new Error('Cannot call "initialize" more than once');
	  ensureServiceIsRunning();
	  initializeWasCalled = true;
	  return Promise.resolve();
	};
	var defaultWD = process.cwd();
	var longLivedService;
	var stopService;
	var ensureServiceIsRunning = () => {
	  if (longLivedService) return longLivedService;
	  let [command, args] = esbuildCommandAndArgs();
	  let child = child_process.spawn(command, args.concat(`--service=${"0.25.9"}`, "--ping"), {
	    windowsHide: true,
	    stdio: ["pipe", "pipe", "inherit"],
	    cwd: defaultWD
	  });
	  let { readFromStdout, afterClose, service } = createChannel({
	    writeToStdin(bytes) {
	      child.stdin.write(bytes, (err) => {
	        if (err) afterClose(err);
	      });
	    },
	    readFileSync: fs2.readFileSync,
	    isSync: false,
	    hasFS: true,
	    esbuild: node_exports
	  });
	  child.stdin.on("error", afterClose);
	  child.on("error", afterClose);
	  const stdin = child.stdin;
	  const stdout = child.stdout;
	  stdout.on("data", readFromStdout);
	  stdout.on("end", afterClose);
	  stopService = () => {
	    stdin.destroy();
	    stdout.destroy();
	    child.kill();
	    initializeWasCalled = false;
	    longLivedService = void 0;
	    stopService = void 0;
	  };
	  let refCount = 0;
	  child.unref();
	  if (stdin.unref) {
	    stdin.unref();
	  }
	  if (stdout.unref) {
	    stdout.unref();
	  }
	  const refs = {
	    ref() {
	      if (++refCount === 1) child.ref();
	    },
	    unref() {
	      if (--refCount === 0) child.unref();
	    }
	  };
	  longLivedService = {
	    build: (options) => new Promise((resolve, reject) => {
	      service.buildOrContext({
	        callName: "build",
	        refs,
	        options,
	        isTTY: isTTY(),
	        defaultWD,
	        callback: (err, res) => err ? reject(err) : resolve(res)
	      });
	    }),
	    context: (options) => new Promise((resolve, reject) => service.buildOrContext({
	      callName: "context",
	      refs,
	      options,
	      isTTY: isTTY(),
	      defaultWD,
	      callback: (err, res) => err ? reject(err) : resolve(res)
	    })),
	    transform: (input, options) => new Promise((resolve, reject) => service.transform({
	      callName: "transform",
	      refs,
	      input,
	      options: options || {},
	      isTTY: isTTY(),
	      fs: fsAsync,
	      callback: (err, res) => err ? reject(err) : resolve(res)
	    })),
	    formatMessages: (messages, options) => new Promise((resolve, reject) => service.formatMessages({
	      callName: "formatMessages",
	      refs,
	      messages,
	      options,
	      callback: (err, res) => err ? reject(err) : resolve(res)
	    })),
	    analyzeMetafile: (metafile, options) => new Promise((resolve, reject) => service.analyzeMetafile({
	      callName: "analyzeMetafile",
	      refs,
	      metafile: typeof metafile === "string" ? metafile : JSON.stringify(metafile),
	      options,
	      callback: (err, res) => err ? reject(err) : resolve(res)
	    }))
	  };
	  return longLivedService;
	};
	var runServiceSync = (callback) => {
	  let [command, args] = esbuildCommandAndArgs();
	  let stdin = new Uint8Array();
	  let { readFromStdout, afterClose, service } = createChannel({
	    writeToStdin(bytes) {
	      if (stdin.length !== 0) throw new Error("Must run at most one command");
	      stdin = bytes;
	    },
	    isSync: true,
	    hasFS: true,
	    esbuild: node_exports
	  });
	  callback(service);
	  let stdout = child_process.execFileSync(command, args.concat(`--service=${"0.25.9"}`), {
	    cwd: defaultWD,
	    windowsHide: true,
	    input: stdin,
	    // We don't know how large the output could be. If it's too large, the
	    // command will fail with ENOBUFS. Reserve 16mb for now since that feels
	    // like it should be enough. Also allow overriding this with an environment
	    // variable.
	    maxBuffer: +process.env.ESBUILD_MAX_BUFFER || 16 * 1024 * 1024
	  });
	  readFromStdout(stdout);
	  afterClose(null);
	};
	var randomFileName = () => {
	  return path2.join(os2.tmpdir(), `esbuild-${crypto.randomBytes(32).toString("hex")}`);
	};
	var workerThreadService = null;
	var startWorkerThreadService = (worker_threads2) => {
	  let { port1: mainPort, port2: workerPort } = new worker_threads2.MessageChannel();
	  let worker = new worker_threads2.Worker(__filename, {
	    workerData: { workerPort, defaultWD, esbuildVersion: "0.25.9" },
	    transferList: [workerPort],
	    // From node's documentation: https://nodejs.org/api/worker_threads.html
	    //
	    //   Take care when launching worker threads from preload scripts (scripts loaded
	    //   and run using the `-r` command line flag). Unless the `execArgv` option is
	    //   explicitly set, new Worker threads automatically inherit the command line flags
	    //   from the running process and will preload the same preload scripts as the main
	    //   thread. If the preload script unconditionally launches a worker thread, every
	    //   thread spawned will spawn another until the application crashes.
	    //
	    execArgv: []
	  });
	  let nextID = 0;
	  let fakeBuildError = (text) => {
	    let error = new Error(`Build failed with 1 error:
error: ${text}`);
	    let errors = [{ id: "", pluginName: "", text, location: null, notes: [], detail: void 0 }];
	    error.errors = errors;
	    error.warnings = [];
	    return error;
	  };
	  let validateBuildSyncOptions = (options) => {
	    if (!options) return;
	    let plugins = options.plugins;
	    if (plugins && plugins.length > 0) throw fakeBuildError(`Cannot use plugins in synchronous API calls`);
	  };
	  let applyProperties = (object, properties) => {
	    for (let key in properties) {
	      object[key] = properties[key];
	    }
	  };
	  let runCallSync = (command, args) => {
	    let id = nextID++;
	    let sharedBuffer = new SharedArrayBuffer(8);
	    let sharedBufferView = new Int32Array(sharedBuffer);
	    let msg = { sharedBuffer, id, command, args };
	    worker.postMessage(msg);
	    let status = Atomics.wait(sharedBufferView, 0, 0);
	    if (status !== "ok" && status !== "not-equal") throw new Error("Internal error: Atomics.wait() failed: " + status);
	    let { message: { id: id2, resolve, reject, properties } } = worker_threads2.receiveMessageOnPort(mainPort);
	    if (id !== id2) throw new Error(`Internal error: Expected id ${id} but got id ${id2}`);
	    if (reject) {
	      applyProperties(reject, properties);
	      throw reject;
	    }
	    return resolve;
	  };
	  worker.unref();
	  return {
	    buildSync(options) {
	      validateBuildSyncOptions(options);
	      return runCallSync("build", [options]);
	    },
	    transformSync(input, options) {
	      return runCallSync("transform", [input, options]);
	    },
	    formatMessagesSync(messages, options) {
	      return runCallSync("formatMessages", [messages, options]);
	    },
	    analyzeMetafileSync(metafile, options) {
	      return runCallSync("analyzeMetafile", [metafile, options]);
	    },
	    stop() {
	      worker.terminate();
	      workerThreadService = null;
	    }
	  };
	};
	var startSyncServiceWorker = () => {
	  let workerPort = worker_threads.workerData.workerPort;
	  let parentPort = worker_threads.parentPort;
	  let extractProperties = (object) => {
	    let properties = {};
	    if (object && typeof object === "object") {
	      for (let key in object) {
	        properties[key] = object[key];
	      }
	    }
	    return properties;
	  };
	  try {
	    let service = ensureServiceIsRunning();
	    defaultWD = worker_threads.workerData.defaultWD;
	    parentPort.on("message", (msg) => {
	      (async () => {
	        let { sharedBuffer, id, command, args } = msg;
	        let sharedBufferView = new Int32Array(sharedBuffer);
	        try {
	          switch (command) {
	            case "build":
	              workerPort.postMessage({ id, resolve: await service.build(args[0]) });
	              break;
	            case "transform":
	              workerPort.postMessage({ id, resolve: await service.transform(args[0], args[1]) });
	              break;
	            case "formatMessages":
	              workerPort.postMessage({ id, resolve: await service.formatMessages(args[0], args[1]) });
	              break;
	            case "analyzeMetafile":
	              workerPort.postMessage({ id, resolve: await service.analyzeMetafile(args[0], args[1]) });
	              break;
	            default:
	              throw new Error(`Invalid command: ${command}`);
	          }
	        } catch (reject) {
	          workerPort.postMessage({ id, reject, properties: extractProperties(reject) });
	        }
	        Atomics.add(sharedBufferView, 0, 1);
	        Atomics.notify(sharedBufferView, 0, Infinity);
	      })();
	    });
	  } catch (reject) {
	    parentPort.on("message", (msg) => {
	      let { sharedBuffer, id } = msg;
	      let sharedBufferView = new Int32Array(sharedBuffer);
	      workerPort.postMessage({ id, reject, properties: extractProperties(reject) });
	      Atomics.add(sharedBufferView, 0, 1);
	      Atomics.notify(sharedBufferView, 0, Infinity);
	    });
	  }
	};
	if (isInternalWorkerThread) {
	  startSyncServiceWorker();
	}
	var node_default = node_exports;
	return main;
}

var mainExports = /*@__PURE__*/ requireMain();

async function loadConfigFile(configFile) {
  let path = configFile.path;
  if (process.platform === "win32") {
    const winPath = path.replace(/\\/g, "/");
    if (!winPath.startsWith("file://")) {
      path = `file:///${winPath}`;
    }
  }
  if (configFile.ts) {
    const buildResult = await mainExports.build({
      entryPoints: [configFile.path],
      bundle: true,
      write: false,
      outdir: "dist",
      platform: "node",
      format: "cjs",
      target: "esnext",
      external: ["../dist", "../dist/*"],
      packages: "external",
      sourcemap: "inline"
    });
    const output = buildResult.outputFiles[0];
    const code = Buffer.from(output.contents).toString("utf8");
    fs$1.writeFileSync(output.path, code);
    const m = new Module(output.path, void 0);
    m.filename = output.path;
    m.paths = Module._nodeModulePaths(node_path.dirname(output.path));
    m._compile(code, output.path);
    return expandModules(m.exports);
  } else {
    const modules = await import(path);
    return expandModules(modules);
  }
}
function expandModules(modules) {
  modules = { ...modules };
  if (modules.__esModule) {
    delete modules.__esModule;
  }
  return modules;
}
async function resolveTaskOptions(task, resolveSubFunctions = false) {
  task = await task;
  if (!resolveSubFunctions && Array.isArray(task)) {
    const results = await Promise.all(task.map((task2) => resolveTaskOptions(task2, true)));
    return results.flat();
  }
  if (typeof task === "function") {
    return resolvePromisesToFlatArray(await task(), task?.name);
  }
  return resolvePromisesToFlatArray(await task, task?.name);
}
async function resolvePromisesToFlatArray(tasks, name) {
  if (!Array.isArray(tasks)) {
    return [await tasks];
  }
  const resolvedTasks = await Promise.all(tasks);
  const returnTasks = [];
  for (const resolvedTask of resolvedTasks) {
    if (Array.isArray(resolvedTask)) {
      returnTasks.push(...resolvedTask);
    } else {
      returnTasks.push(resolvedTask);
    }
  }
  return returnTasks;
}
function mustGetAvailableConfigFile(root, params) {
  const found = getAvailableConfigFile(root, params);
  if (!found) {
    throw new Error("No config file found. Please create a fusionfile.js or fusionfile.ts in the root directory.");
  }
  return found;
}
function getAvailableConfigFile(root, params) {
  let found = params?.config;
  if (found) {
    if (!node_path.isAbsolute(found)) {
      found = node_path.resolve(root, found);
    }
    if (fs$1.existsSync(found)) {
      return {
        path: found,
        // get filename from file path
        filename: found.split("/").pop() || "",
        type: getConfigModuleType(found),
        ts: isConfigTypeScript(found)
      };
    }
    return null;
  }
  return findDefaultConfig(root);
}
function findDefaultConfig(root) {
  let file = node_path.resolve(root, "fusionfile.js");
  if (fs$1.existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "commonjs",
      ts: false
    };
  }
  file = node_path.resolve(root, "fusionfile.mjs");
  if (fs$1.existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "module",
      ts: false
    };
  }
  file = node_path.resolve(root, "fusionfile.ts");
  if (fs$1.existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "module",
      ts: true
    };
  }
  file = node_path.resolve(root, "fusionfile.mts");
  if (fs$1.existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "module",
      ts: true
    };
  }
  return null;
}
function getConfigModuleType(file) {
  let type = "unknown";
  if (file.endsWith(".cjs")) {
    type = "commonjs";
  } else if (file.endsWith(".mjs")) {
    type = "module";
  } else if (file.endsWith(".ts") || file.endsWith(".mts")) {
    type = "module";
  }
  return type;
}
function isConfigTypeScript(file) {
  return file.endsWith(".ts") || file.endsWith(".mts");
}

async function displayAvailableTasks(tasks) {
  const keys = Object.keys(tasks);
  keys.sort((a, b) => {
    if (a === "default") {
      return -1;
    }
    if (b === "default") {
      return 1;
    }
    return a.localeCompare(b);
  });
  const nodes = [];
  for (const key of keys) {
    const task = tasks[key];
    nodes.push(await describeTasks(key, task));
  }
  const text = archy({
    label: chalk.magenta("Available Tasks"),
    nodes
  });
  console.log(text);
}
async function describeTasks(name, tasks) {
  const nodes = [];
  tasks = forceArray(await tasks);
  for (let task of tasks) {
    const processors = await resolveTaskOptions(task, true);
    for (const processor of processors) {
      if (typeof processor === "function") {
        nodes.push(
          await describeTasks(processor.name, processor)
        );
      } else {
        nodes.push(...await describeProcessor(processor));
      }
    }
  }
  return {
    label: chalk.cyan(name),
    nodes
  };
}
async function describeProcessor(processor) {
  const results = await processor.preview();
  return Promise.all(results.map((result) => describeProcessorPreview(result)));
}
async function describeProcessorPreview(preview) {
  const str = [];
  const { input: entry, output, extra } = preview;
  const inputStr = chalk.yellow(entry);
  str.push(`Input: ${inputStr}`);
  const outStr = chalk.green(output);
  str.push(`Output: ${outStr}`);
  return str.join(" - ");
}

function selectRunningTasks(input, tasks) {
  input = uniq(input);
  if (input.length === 0) {
    input.push("default");
  }
  const selected = {};
  for (const name of input) {
    if (tasks[name]) {
      selected[name] = tasks[name];
    } else {
      throw new Error(`Task "${chalk.cyan(name)}" not found in fusion config.`);
    }
  }
  return selected;
}
async function resolveAllTasksAsProcessors(tasks) {
  const cache = {};
  const allTasks = {};
  for (const name in tasks) {
    const task = tasks[name];
    allTasks[name] = await resolveTaskAsFlat(name, task, cache);
  }
  return allTasks;
}
async function resolveTaskAsFlat(name, task, cache) {
  const results = [];
  if (Array.isArray(task)) {
    for (const n in task) {
      const t = task[n];
      results.push(...await resolveTaskAsFlat(n, t, cache));
    }
  } else if (typeof task === "function") {
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
  } else {
    results.push(await task);
  }
  return results;
}

var utils$1 = {};

var hasRequiredUtils$1;

function requireUtils$1 () {
	if (hasRequiredUtils$1) return utils$1;
	hasRequiredUtils$1 = 1;
	(function (exports) {

		exports.isInteger = num => {
		  if (typeof num === 'number') {
		    return Number.isInteger(num);
		  }
		  if (typeof num === 'string' && num.trim() !== '') {
		    return Number.isInteger(Number(num));
		  }
		  return false;
		};

		/**
		 * Find a node of the given type
		 */

		exports.find = (node, type) => node.nodes.find(node => node.type === type);

		/**
		 * Find a node of the given type
		 */

		exports.exceedsLimit = (min, max, step = 1, limit) => {
		  if (limit === false) return false;
		  if (!exports.isInteger(min) || !exports.isInteger(max)) return false;
		  return ((Number(max) - Number(min)) / Number(step)) >= limit;
		};

		/**
		 * Escape the given node with '\\' before node.value
		 */

		exports.escapeNode = (block, n = 0, type) => {
		  const node = block.nodes[n];
		  if (!node) return;

		  if ((type && node.type === type) || node.type === 'open' || node.type === 'close') {
		    if (node.escaped !== true) {
		      node.value = '\\' + node.value;
		      node.escaped = true;
		    }
		  }
		};

		/**
		 * Returns true if the given brace node should be enclosed in literal braces
		 */

		exports.encloseBrace = node => {
		  if (node.type !== 'brace') return false;
		  if ((node.commas >> 0 + node.ranges >> 0) === 0) {
		    node.invalid = true;
		    return true;
		  }
		  return false;
		};

		/**
		 * Returns true if a brace node is invalid.
		 */

		exports.isInvalidBrace = block => {
		  if (block.type !== 'brace') return false;
		  if (block.invalid === true || block.dollar) return true;
		  if ((block.commas >> 0 + block.ranges >> 0) === 0) {
		    block.invalid = true;
		    return true;
		  }
		  if (block.open !== true || block.close !== true) {
		    block.invalid = true;
		    return true;
		  }
		  return false;
		};

		/**
		 * Returns true if a node is an open or close node
		 */

		exports.isOpenOrClose = node => {
		  if (node.type === 'open' || node.type === 'close') {
		    return true;
		  }
		  return node.open === true || node.close === true;
		};

		/**
		 * Reduce an array of text nodes.
		 */

		exports.reduce = nodes => nodes.reduce((acc, node) => {
		  if (node.type === 'text') acc.push(node.value);
		  if (node.type === 'range') node.type = 'text';
		  return acc;
		}, []);

		/**
		 * Flatten an array
		 */

		exports.flatten = (...args) => {
		  const result = [];

		  const flat = arr => {
		    for (let i = 0; i < arr.length; i++) {
		      const ele = arr[i];

		      if (Array.isArray(ele)) {
		        flat(ele);
		        continue;
		      }

		      if (ele !== undefined) {
		        result.push(ele);
		      }
		    }
		    return result;
		  };

		  flat(args);
		  return result;
		}; 
	} (utils$1));
	return utils$1;
}

var stringify;
var hasRequiredStringify;

function requireStringify () {
	if (hasRequiredStringify) return stringify;
	hasRequiredStringify = 1;

	const utils = /*@__PURE__*/ requireUtils$1();

	stringify = (ast, options = {}) => {
	  const stringify = (node, parent = {}) => {
	    const invalidBlock = options.escapeInvalid && utils.isInvalidBrace(parent);
	    const invalidNode = node.invalid === true && options.escapeInvalid === true;
	    let output = '';

	    if (node.value) {
	      if ((invalidBlock || invalidNode) && utils.isOpenOrClose(node)) {
	        return '\\' + node.value;
	      }
	      return node.value;
	    }

	    if (node.value) {
	      return node.value;
	    }

	    if (node.nodes) {
	      for (const child of node.nodes) {
	        output += stringify(child);
	      }
	    }
	    return output;
	  };

	  return stringify(ast);
	};
	return stringify;
}

/*!
 * is-number <https://github.com/jonschlinkert/is-number>
 *
 * Copyright (c) 2014-present, Jon Schlinkert.
 * Released under the MIT License.
 */

var isNumber;
var hasRequiredIsNumber;

function requireIsNumber () {
	if (hasRequiredIsNumber) return isNumber;
	hasRequiredIsNumber = 1;

	isNumber = function(num) {
	  if (typeof num === 'number') {
	    return num - num === 0;
	  }
	  if (typeof num === 'string' && num.trim() !== '') {
	    return Number.isFinite ? Number.isFinite(+num) : isFinite(+num);
	  }
	  return false;
	};
	return isNumber;
}

/*!
 * to-regex-range <https://github.com/micromatch/to-regex-range>
 *
 * Copyright (c) 2015-present, Jon Schlinkert.
 * Released under the MIT License.
 */

var toRegexRange_1;
var hasRequiredToRegexRange;

function requireToRegexRange () {
	if (hasRequiredToRegexRange) return toRegexRange_1;
	hasRequiredToRegexRange = 1;

	const isNumber = /*@__PURE__*/ requireIsNumber();

	const toRegexRange = (min, max, options) => {
	  if (isNumber(min) === false) {
	    throw new TypeError('toRegexRange: expected the first argument to be a number');
	  }

	  if (max === void 0 || min === max) {
	    return String(min);
	  }

	  if (isNumber(max) === false) {
	    throw new TypeError('toRegexRange: expected the second argument to be a number.');
	  }

	  let opts = { relaxZeros: true, ...options };
	  if (typeof opts.strictZeros === 'boolean') {
	    opts.relaxZeros = opts.strictZeros === false;
	  }

	  let relax = String(opts.relaxZeros);
	  let shorthand = String(opts.shorthand);
	  let capture = String(opts.capture);
	  let wrap = String(opts.wrap);
	  let cacheKey = min + ':' + max + '=' + relax + shorthand + capture + wrap;

	  if (toRegexRange.cache.hasOwnProperty(cacheKey)) {
	    return toRegexRange.cache[cacheKey].result;
	  }

	  let a = Math.min(min, max);
	  let b = Math.max(min, max);

	  if (Math.abs(a - b) === 1) {
	    let result = min + '|' + max;
	    if (opts.capture) {
	      return `(${result})`;
	    }
	    if (opts.wrap === false) {
	      return result;
	    }
	    return `(?:${result})`;
	  }

	  let isPadded = hasPadding(min) || hasPadding(max);
	  let state = { min, max, a, b };
	  let positives = [];
	  let negatives = [];

	  if (isPadded) {
	    state.isPadded = isPadded;
	    state.maxLen = String(state.max).length;
	  }

	  if (a < 0) {
	    let newMin = b < 0 ? Math.abs(b) : 1;
	    negatives = splitToPatterns(newMin, Math.abs(a), state, opts);
	    a = state.a = 0;
	  }

	  if (b >= 0) {
	    positives = splitToPatterns(a, b, state, opts);
	  }

	  state.negatives = negatives;
	  state.positives = positives;
	  state.result = collatePatterns(negatives, positives);

	  if (opts.capture === true) {
	    state.result = `(${state.result})`;
	  } else if (opts.wrap !== false && (positives.length + negatives.length) > 1) {
	    state.result = `(?:${state.result})`;
	  }

	  toRegexRange.cache[cacheKey] = state;
	  return state.result;
	};

	function collatePatterns(neg, pos, options) {
	  let onlyNegative = filterPatterns(neg, pos, '-', false) || [];
	  let onlyPositive = filterPatterns(pos, neg, '', false) || [];
	  let intersected = filterPatterns(neg, pos, '-?', true) || [];
	  let subpatterns = onlyNegative.concat(intersected).concat(onlyPositive);
	  return subpatterns.join('|');
	}

	function splitToRanges(min, max) {
	  let nines = 1;
	  let zeros = 1;

	  let stop = countNines(min, nines);
	  let stops = new Set([max]);

	  while (min <= stop && stop <= max) {
	    stops.add(stop);
	    nines += 1;
	    stop = countNines(min, nines);
	  }

	  stop = countZeros(max + 1, zeros) - 1;

	  while (min < stop && stop <= max) {
	    stops.add(stop);
	    zeros += 1;
	    stop = countZeros(max + 1, zeros) - 1;
	  }

	  stops = [...stops];
	  stops.sort(compare);
	  return stops;
	}

	/**
	 * Convert a range to a regex pattern
	 * @param {Number} `start`
	 * @param {Number} `stop`
	 * @return {String}
	 */

	function rangeToPattern(start, stop, options) {
	  if (start === stop) {
	    return { pattern: start, count: [], digits: 0 };
	  }

	  let zipped = zip(start, stop);
	  let digits = zipped.length;
	  let pattern = '';
	  let count = 0;

	  for (let i = 0; i < digits; i++) {
	    let [startDigit, stopDigit] = zipped[i];

	    if (startDigit === stopDigit) {
	      pattern += startDigit;

	    } else if (startDigit !== '0' || stopDigit !== '9') {
	      pattern += toCharacterClass(startDigit, stopDigit);

	    } else {
	      count++;
	    }
	  }

	  if (count) {
	    pattern += options.shorthand === true ? '\\d' : '[0-9]';
	  }

	  return { pattern, count: [count], digits };
	}

	function splitToPatterns(min, max, tok, options) {
	  let ranges = splitToRanges(min, max);
	  let tokens = [];
	  let start = min;
	  let prev;

	  for (let i = 0; i < ranges.length; i++) {
	    let max = ranges[i];
	    let obj = rangeToPattern(String(start), String(max), options);
	    let zeros = '';

	    if (!tok.isPadded && prev && prev.pattern === obj.pattern) {
	      if (prev.count.length > 1) {
	        prev.count.pop();
	      }

	      prev.count.push(obj.count[0]);
	      prev.string = prev.pattern + toQuantifier(prev.count);
	      start = max + 1;
	      continue;
	    }

	    if (tok.isPadded) {
	      zeros = padZeros(max, tok, options);
	    }

	    obj.string = zeros + obj.pattern + toQuantifier(obj.count);
	    tokens.push(obj);
	    start = max + 1;
	    prev = obj;
	  }

	  return tokens;
	}

	function filterPatterns(arr, comparison, prefix, intersection, options) {
	  let result = [];

	  for (let ele of arr) {
	    let { string } = ele;

	    // only push if _both_ are negative...
	    if (!intersection && !contains(comparison, 'string', string)) {
	      result.push(prefix + string);
	    }

	    // or _both_ are positive
	    if (intersection && contains(comparison, 'string', string)) {
	      result.push(prefix + string);
	    }
	  }
	  return result;
	}

	/**
	 * Zip strings
	 */

	function zip(a, b) {
	  let arr = [];
	  for (let i = 0; i < a.length; i++) arr.push([a[i], b[i]]);
	  return arr;
	}

	function compare(a, b) {
	  return a > b ? 1 : b > a ? -1 : 0;
	}

	function contains(arr, key, val) {
	  return arr.some(ele => ele[key] === val);
	}

	function countNines(min, len) {
	  return Number(String(min).slice(0, -len) + '9'.repeat(len));
	}

	function countZeros(integer, zeros) {
	  return integer - (integer % Math.pow(10, zeros));
	}

	function toQuantifier(digits) {
	  let [start = 0, stop = ''] = digits;
	  if (stop || start > 1) {
	    return `{${start + (stop ? ',' + stop : '')}}`;
	  }
	  return '';
	}

	function toCharacterClass(a, b, options) {
	  return `[${a}${(b - a === 1) ? '' : '-'}${b}]`;
	}

	function hasPadding(str) {
	  return /^-?(0+)\d/.test(str);
	}

	function padZeros(value, tok, options) {
	  if (!tok.isPadded) {
	    return value;
	  }

	  let diff = Math.abs(tok.maxLen - String(value).length);
	  let relax = options.relaxZeros !== false;

	  switch (diff) {
	    case 0:
	      return '';
	    case 1:
	      return relax ? '0?' : '0';
	    case 2:
	      return relax ? '0{0,2}' : '00';
	    default: {
	      return relax ? `0{0,${diff}}` : `0{${diff}}`;
	    }
	  }
	}

	/**
	 * Cache
	 */

	toRegexRange.cache = {};
	toRegexRange.clearCache = () => (toRegexRange.cache = {});

	/**
	 * Expose `toRegexRange`
	 */

	toRegexRange_1 = toRegexRange;
	return toRegexRange_1;
}

/*!
 * fill-range <https://github.com/jonschlinkert/fill-range>
 *
 * Copyright (c) 2014-present, Jon Schlinkert.
 * Licensed under the MIT License.
 */

var fillRange;
var hasRequiredFillRange;

function requireFillRange () {
	if (hasRequiredFillRange) return fillRange;
	hasRequiredFillRange = 1;

	const util = require$$0$2;
	const toRegexRange = /*@__PURE__*/ requireToRegexRange();

	const isObject = val => val !== null && typeof val === 'object' && !Array.isArray(val);

	const transform = toNumber => {
	  return value => toNumber === true ? Number(value) : String(value);
	};

	const isValidValue = value => {
	  return typeof value === 'number' || (typeof value === 'string' && value !== '');
	};

	const isNumber = num => Number.isInteger(+num);

	const zeros = input => {
	  let value = `${input}`;
	  let index = -1;
	  if (value[0] === '-') value = value.slice(1);
	  if (value === '0') return false;
	  while (value[++index] === '0');
	  return index > 0;
	};

	const stringify = (start, end, options) => {
	  if (typeof start === 'string' || typeof end === 'string') {
	    return true;
	  }
	  return options.stringify === true;
	};

	const pad = (input, maxLength, toNumber) => {
	  if (maxLength > 0) {
	    let dash = input[0] === '-' ? '-' : '';
	    if (dash) input = input.slice(1);
	    input = (dash + input.padStart(dash ? maxLength - 1 : maxLength, '0'));
	  }
	  if (toNumber === false) {
	    return String(input);
	  }
	  return input;
	};

	const toMaxLen = (input, maxLength) => {
	  let negative = input[0] === '-' ? '-' : '';
	  if (negative) {
	    input = input.slice(1);
	    maxLength--;
	  }
	  while (input.length < maxLength) input = '0' + input;
	  return negative ? ('-' + input) : input;
	};

	const toSequence = (parts, options, maxLen) => {
	  parts.negatives.sort((a, b) => a < b ? -1 : a > b ? 1 : 0);
	  parts.positives.sort((a, b) => a < b ? -1 : a > b ? 1 : 0);

	  let prefix = options.capture ? '' : '?:';
	  let positives = '';
	  let negatives = '';
	  let result;

	  if (parts.positives.length) {
	    positives = parts.positives.map(v => toMaxLen(String(v), maxLen)).join('|');
	  }

	  if (parts.negatives.length) {
	    negatives = `-(${prefix}${parts.negatives.map(v => toMaxLen(String(v), maxLen)).join('|')})`;
	  }

	  if (positives && negatives) {
	    result = `${positives}|${negatives}`;
	  } else {
	    result = positives || negatives;
	  }

	  if (options.wrap) {
	    return `(${prefix}${result})`;
	  }

	  return result;
	};

	const toRange = (a, b, isNumbers, options) => {
	  if (isNumbers) {
	    return toRegexRange(a, b, { wrap: false, ...options });
	  }

	  let start = String.fromCharCode(a);
	  if (a === b) return start;

	  let stop = String.fromCharCode(b);
	  return `[${start}-${stop}]`;
	};

	const toRegex = (start, end, options) => {
	  if (Array.isArray(start)) {
	    let wrap = options.wrap === true;
	    let prefix = options.capture ? '' : '?:';
	    return wrap ? `(${prefix}${start.join('|')})` : start.join('|');
	  }
	  return toRegexRange(start, end, options);
	};

	const rangeError = (...args) => {
	  return new RangeError('Invalid range arguments: ' + util.inspect(...args));
	};

	const invalidRange = (start, end, options) => {
	  if (options.strictRanges === true) throw rangeError([start, end]);
	  return [];
	};

	const invalidStep = (step, options) => {
	  if (options.strictRanges === true) {
	    throw new TypeError(`Expected step "${step}" to be a number`);
	  }
	  return [];
	};

	const fillNumbers = (start, end, step = 1, options = {}) => {
	  let a = Number(start);
	  let b = Number(end);

	  if (!Number.isInteger(a) || !Number.isInteger(b)) {
	    if (options.strictRanges === true) throw rangeError([start, end]);
	    return [];
	  }

	  // fix negative zero
	  if (a === 0) a = 0;
	  if (b === 0) b = 0;

	  let descending = a > b;
	  let startString = String(start);
	  let endString = String(end);
	  let stepString = String(step);
	  step = Math.max(Math.abs(step), 1);

	  let padded = zeros(startString) || zeros(endString) || zeros(stepString);
	  let maxLen = padded ? Math.max(startString.length, endString.length, stepString.length) : 0;
	  let toNumber = padded === false && stringify(start, end, options) === false;
	  let format = options.transform || transform(toNumber);

	  if (options.toRegex && step === 1) {
	    return toRange(toMaxLen(start, maxLen), toMaxLen(end, maxLen), true, options);
	  }

	  let parts = { negatives: [], positives: [] };
	  let push = num => parts[num < 0 ? 'negatives' : 'positives'].push(Math.abs(num));
	  let range = [];
	  let index = 0;

	  while (descending ? a >= b : a <= b) {
	    if (options.toRegex === true && step > 1) {
	      push(a);
	    } else {
	      range.push(pad(format(a, index), maxLen, toNumber));
	    }
	    a = descending ? a - step : a + step;
	    index++;
	  }

	  if (options.toRegex === true) {
	    return step > 1
	      ? toSequence(parts, options, maxLen)
	      : toRegex(range, null, { wrap: false, ...options });
	  }

	  return range;
	};

	const fillLetters = (start, end, step = 1, options = {}) => {
	  if ((!isNumber(start) && start.length > 1) || (!isNumber(end) && end.length > 1)) {
	    return invalidRange(start, end, options);
	  }

	  let format = options.transform || (val => String.fromCharCode(val));
	  let a = `${start}`.charCodeAt(0);
	  let b = `${end}`.charCodeAt(0);

	  let descending = a > b;
	  let min = Math.min(a, b);
	  let max = Math.max(a, b);

	  if (options.toRegex && step === 1) {
	    return toRange(min, max, false, options);
	  }

	  let range = [];
	  let index = 0;

	  while (descending ? a >= b : a <= b) {
	    range.push(format(a, index));
	    a = descending ? a - step : a + step;
	    index++;
	  }

	  if (options.toRegex === true) {
	    return toRegex(range, null, { wrap: false, options });
	  }

	  return range;
	};

	const fill = (start, end, step, options = {}) => {
	  if (end == null && isValidValue(start)) {
	    return [start];
	  }

	  if (!isValidValue(start) || !isValidValue(end)) {
	    return invalidRange(start, end, options);
	  }

	  if (typeof step === 'function') {
	    return fill(start, end, 1, { transform: step });
	  }

	  if (isObject(step)) {
	    return fill(start, end, 0, step);
	  }

	  let opts = { ...options };
	  if (opts.capture === true) opts.wrap = true;
	  step = step || opts.step || 1;

	  if (!isNumber(step)) {
	    if (step != null && !isObject(step)) return invalidStep(step, opts);
	    return fill(start, end, 1, step);
	  }

	  if (isNumber(start) && isNumber(end)) {
	    return fillNumbers(start, end, step, opts);
	  }

	  return fillLetters(start, end, Math.max(Math.abs(step), 1), opts);
	};

	fillRange = fill;
	return fillRange;
}

var compile_1;
var hasRequiredCompile;

function requireCompile () {
	if (hasRequiredCompile) return compile_1;
	hasRequiredCompile = 1;

	const fill = /*@__PURE__*/ requireFillRange();
	const utils = /*@__PURE__*/ requireUtils$1();

	const compile = (ast, options = {}) => {
	  const walk = (node, parent = {}) => {
	    const invalidBlock = utils.isInvalidBrace(parent);
	    const invalidNode = node.invalid === true && options.escapeInvalid === true;
	    const invalid = invalidBlock === true || invalidNode === true;
	    const prefix = options.escapeInvalid === true ? '\\' : '';
	    let output = '';

	    if (node.isOpen === true) {
	      return prefix + node.value;
	    }

	    if (node.isClose === true) {
	      console.log('node.isClose', prefix, node.value);
	      return prefix + node.value;
	    }

	    if (node.type === 'open') {
	      return invalid ? prefix + node.value : '(';
	    }

	    if (node.type === 'close') {
	      return invalid ? prefix + node.value : ')';
	    }

	    if (node.type === 'comma') {
	      return node.prev.type === 'comma' ? '' : invalid ? node.value : '|';
	    }

	    if (node.value) {
	      return node.value;
	    }

	    if (node.nodes && node.ranges > 0) {
	      const args = utils.reduce(node.nodes);
	      const range = fill(...args, { ...options, wrap: false, toRegex: true, strictZeros: true });

	      if (range.length !== 0) {
	        return args.length > 1 && range.length > 1 ? `(${range})` : range;
	      }
	    }

	    if (node.nodes) {
	      for (const child of node.nodes) {
	        output += walk(child, node);
	      }
	    }

	    return output;
	  };

	  return walk(ast);
	};

	compile_1 = compile;
	return compile_1;
}

var expand_1;
var hasRequiredExpand;

function requireExpand () {
	if (hasRequiredExpand) return expand_1;
	hasRequiredExpand = 1;

	const fill = /*@__PURE__*/ requireFillRange();
	const stringify = /*@__PURE__*/ requireStringify();
	const utils = /*@__PURE__*/ requireUtils$1();

	const append = (queue = '', stash = '', enclose = false) => {
	  const result = [];

	  queue = [].concat(queue);
	  stash = [].concat(stash);

	  if (!stash.length) return queue;
	  if (!queue.length) {
	    return enclose ? utils.flatten(stash).map(ele => `{${ele}}`) : stash;
	  }

	  for (const item of queue) {
	    if (Array.isArray(item)) {
	      for (const value of item) {
	        result.push(append(value, stash, enclose));
	      }
	    } else {
	      for (let ele of stash) {
	        if (enclose === true && typeof ele === 'string') ele = `{${ele}}`;
	        result.push(Array.isArray(ele) ? append(item, ele, enclose) : item + ele);
	      }
	    }
	  }
	  return utils.flatten(result);
	};

	const expand = (ast, options = {}) => {
	  const rangeLimit = options.rangeLimit === undefined ? 1000 : options.rangeLimit;

	  const walk = (node, parent = {}) => {
	    node.queue = [];

	    let p = parent;
	    let q = parent.queue;

	    while (p.type !== 'brace' && p.type !== 'root' && p.parent) {
	      p = p.parent;
	      q = p.queue;
	    }

	    if (node.invalid || node.dollar) {
	      q.push(append(q.pop(), stringify(node, options)));
	      return;
	    }

	    if (node.type === 'brace' && node.invalid !== true && node.nodes.length === 2) {
	      q.push(append(q.pop(), ['{}']));
	      return;
	    }

	    if (node.nodes && node.ranges > 0) {
	      const args = utils.reduce(node.nodes);

	      if (utils.exceedsLimit(...args, options.step, rangeLimit)) {
	        throw new RangeError('expanded array length exceeds range limit. Use options.rangeLimit to increase or disable the limit.');
	      }

	      let range = fill(...args, options);
	      if (range.length === 0) {
	        range = stringify(node, options);
	      }

	      q.push(append(q.pop(), range));
	      node.nodes = [];
	      return;
	    }

	    const enclose = utils.encloseBrace(node);
	    let queue = node.queue;
	    let block = node;

	    while (block.type !== 'brace' && block.type !== 'root' && block.parent) {
	      block = block.parent;
	      queue = block.queue;
	    }

	    for (let i = 0; i < node.nodes.length; i++) {
	      const child = node.nodes[i];

	      if (child.type === 'comma' && node.type === 'brace') {
	        if (i === 1) queue.push('');
	        queue.push('');
	        continue;
	      }

	      if (child.type === 'close') {
	        q.push(append(q.pop(), queue, enclose));
	        continue;
	      }

	      if (child.value && child.type !== 'open') {
	        queue.push(append(queue.pop(), child.value));
	        continue;
	      }

	      if (child.nodes) {
	        walk(child, node);
	      }
	    }

	    return queue;
	  };

	  return utils.flatten(walk(ast));
	};

	expand_1 = expand;
	return expand_1;
}

var constants$1;
var hasRequiredConstants$1;

function requireConstants$1 () {
	if (hasRequiredConstants$1) return constants$1;
	hasRequiredConstants$1 = 1;

	constants$1 = {
	  MAX_LENGTH: 10000,

	  // Digits
	  CHAR_0: '0', /* 0 */
	  CHAR_9: '9', /* 9 */

	  // Alphabet chars.
	  CHAR_UPPERCASE_A: 'A', /* A */
	  CHAR_LOWERCASE_A: 'a', /* a */
	  CHAR_UPPERCASE_Z: 'Z', /* Z */
	  CHAR_LOWERCASE_Z: 'z', /* z */

	  CHAR_LEFT_PARENTHESES: '(', /* ( */
	  CHAR_RIGHT_PARENTHESES: ')', /* ) */

	  CHAR_ASTERISK: '*', /* * */

	  // Non-alphabetic chars.
	  CHAR_AMPERSAND: '&', /* & */
	  CHAR_AT: '@', /* @ */
	  CHAR_BACKSLASH: '\\', /* \ */
	  CHAR_BACKTICK: '`', /* ` */
	  CHAR_CARRIAGE_RETURN: '\r', /* \r */
	  CHAR_CIRCUMFLEX_ACCENT: '^', /* ^ */
	  CHAR_COLON: ':', /* : */
	  CHAR_COMMA: ',', /* , */
	  CHAR_DOLLAR: '$', /* . */
	  CHAR_DOT: '.', /* . */
	  CHAR_DOUBLE_QUOTE: '"', /* " */
	  CHAR_EQUAL: '=', /* = */
	  CHAR_EXCLAMATION_MARK: '!', /* ! */
	  CHAR_FORM_FEED: '\f', /* \f */
	  CHAR_FORWARD_SLASH: '/', /* / */
	  CHAR_HASH: '#', /* # */
	  CHAR_HYPHEN_MINUS: '-', /* - */
	  CHAR_LEFT_ANGLE_BRACKET: '<', /* < */
	  CHAR_LEFT_CURLY_BRACE: '{', /* { */
	  CHAR_LEFT_SQUARE_BRACKET: '[', /* [ */
	  CHAR_LINE_FEED: '\n', /* \n */
	  CHAR_NO_BREAK_SPACE: '\u00A0', /* \u00A0 */
	  CHAR_PERCENT: '%', /* % */
	  CHAR_PLUS: '+', /* + */
	  CHAR_QUESTION_MARK: '?', /* ? */
	  CHAR_RIGHT_ANGLE_BRACKET: '>', /* > */
	  CHAR_RIGHT_CURLY_BRACE: '}', /* } */
	  CHAR_RIGHT_SQUARE_BRACKET: ']', /* ] */
	  CHAR_SEMICOLON: ';', /* ; */
	  CHAR_SINGLE_QUOTE: '\'', /* ' */
	  CHAR_SPACE: ' ', /*   */
	  CHAR_TAB: '\t', /* \t */
	  CHAR_UNDERSCORE: '_', /* _ */
	  CHAR_VERTICAL_LINE: '|', /* | */
	  CHAR_ZERO_WIDTH_NOBREAK_SPACE: '\uFEFF' /* \uFEFF */
	};
	return constants$1;
}

var parse_1$1;
var hasRequiredParse$1;

function requireParse$1 () {
	if (hasRequiredParse$1) return parse_1$1;
	hasRequiredParse$1 = 1;

	const stringify = /*@__PURE__*/ requireStringify();

	/**
	 * Constants
	 */

	const {
	  MAX_LENGTH,
	  CHAR_BACKSLASH, /* \ */
	  CHAR_BACKTICK, /* ` */
	  CHAR_COMMA, /* , */
	  CHAR_DOT, /* . */
	  CHAR_LEFT_PARENTHESES, /* ( */
	  CHAR_RIGHT_PARENTHESES, /* ) */
	  CHAR_LEFT_CURLY_BRACE, /* { */
	  CHAR_RIGHT_CURLY_BRACE, /* } */
	  CHAR_LEFT_SQUARE_BRACKET, /* [ */
	  CHAR_RIGHT_SQUARE_BRACKET, /* ] */
	  CHAR_DOUBLE_QUOTE, /* " */
	  CHAR_SINGLE_QUOTE, /* ' */
	  CHAR_NO_BREAK_SPACE,
	  CHAR_ZERO_WIDTH_NOBREAK_SPACE
	} = /*@__PURE__*/ requireConstants$1();

	/**
	 * parse
	 */

	const parse = (input, options = {}) => {
	  if (typeof input !== 'string') {
	    throw new TypeError('Expected a string');
	  }

	  const opts = options || {};
	  const max = typeof opts.maxLength === 'number' ? Math.min(MAX_LENGTH, opts.maxLength) : MAX_LENGTH;
	  if (input.length > max) {
	    throw new SyntaxError(`Input length (${input.length}), exceeds max characters (${max})`);
	  }

	  const ast = { type: 'root', input, nodes: [] };
	  const stack = [ast];
	  let block = ast;
	  let prev = ast;
	  let brackets = 0;
	  const length = input.length;
	  let index = 0;
	  let depth = 0;
	  let value;

	  /**
	   * Helpers
	   */

	  const advance = () => input[index++];
	  const push = node => {
	    if (node.type === 'text' && prev.type === 'dot') {
	      prev.type = 'text';
	    }

	    if (prev && prev.type === 'text' && node.type === 'text') {
	      prev.value += node.value;
	      return;
	    }

	    block.nodes.push(node);
	    node.parent = block;
	    node.prev = prev;
	    prev = node;
	    return node;
	  };

	  push({ type: 'bos' });

	  while (index < length) {
	    block = stack[stack.length - 1];
	    value = advance();

	    /**
	     * Invalid chars
	     */

	    if (value === CHAR_ZERO_WIDTH_NOBREAK_SPACE || value === CHAR_NO_BREAK_SPACE) {
	      continue;
	    }

	    /**
	     * Escaped chars
	     */

	    if (value === CHAR_BACKSLASH) {
	      push({ type: 'text', value: (options.keepEscaping ? value : '') + advance() });
	      continue;
	    }

	    /**
	     * Right square bracket (literal): ']'
	     */

	    if (value === CHAR_RIGHT_SQUARE_BRACKET) {
	      push({ type: 'text', value: '\\' + value });
	      continue;
	    }

	    /**
	     * Left square bracket: '['
	     */

	    if (value === CHAR_LEFT_SQUARE_BRACKET) {
	      brackets++;

	      let next;

	      while (index < length && (next = advance())) {
	        value += next;

	        if (next === CHAR_LEFT_SQUARE_BRACKET) {
	          brackets++;
	          continue;
	        }

	        if (next === CHAR_BACKSLASH) {
	          value += advance();
	          continue;
	        }

	        if (next === CHAR_RIGHT_SQUARE_BRACKET) {
	          brackets--;

	          if (brackets === 0) {
	            break;
	          }
	        }
	      }

	      push({ type: 'text', value });
	      continue;
	    }

	    /**
	     * Parentheses
	     */

	    if (value === CHAR_LEFT_PARENTHESES) {
	      block = push({ type: 'paren', nodes: [] });
	      stack.push(block);
	      push({ type: 'text', value });
	      continue;
	    }

	    if (value === CHAR_RIGHT_PARENTHESES) {
	      if (block.type !== 'paren') {
	        push({ type: 'text', value });
	        continue;
	      }
	      block = stack.pop();
	      push({ type: 'text', value });
	      block = stack[stack.length - 1];
	      continue;
	    }

	    /**
	     * Quotes: '|"|`
	     */

	    if (value === CHAR_DOUBLE_QUOTE || value === CHAR_SINGLE_QUOTE || value === CHAR_BACKTICK) {
	      const open = value;
	      let next;

	      if (options.keepQuotes !== true) {
	        value = '';
	      }

	      while (index < length && (next = advance())) {
	        if (next === CHAR_BACKSLASH) {
	          value += next + advance();
	          continue;
	        }

	        if (next === open) {
	          if (options.keepQuotes === true) value += next;
	          break;
	        }

	        value += next;
	      }

	      push({ type: 'text', value });
	      continue;
	    }

	    /**
	     * Left curly brace: '{'
	     */

	    if (value === CHAR_LEFT_CURLY_BRACE) {
	      depth++;

	      const dollar = prev.value && prev.value.slice(-1) === '$' || block.dollar === true;
	      const brace = {
	        type: 'brace',
	        open: true,
	        close: false,
	        dollar,
	        depth,
	        commas: 0,
	        ranges: 0,
	        nodes: []
	      };

	      block = push(brace);
	      stack.push(block);
	      push({ type: 'open', value });
	      continue;
	    }

	    /**
	     * Right curly brace: '}'
	     */

	    if (value === CHAR_RIGHT_CURLY_BRACE) {
	      if (block.type !== 'brace') {
	        push({ type: 'text', value });
	        continue;
	      }

	      const type = 'close';
	      block = stack.pop();
	      block.close = true;

	      push({ type, value });
	      depth--;

	      block = stack[stack.length - 1];
	      continue;
	    }

	    /**
	     * Comma: ','
	     */

	    if (value === CHAR_COMMA && depth > 0) {
	      if (block.ranges > 0) {
	        block.ranges = 0;
	        const open = block.nodes.shift();
	        block.nodes = [open, { type: 'text', value: stringify(block) }];
	      }

	      push({ type: 'comma', value });
	      block.commas++;
	      continue;
	    }

	    /**
	     * Dot: '.'
	     */

	    if (value === CHAR_DOT && depth > 0 && block.commas === 0) {
	      const siblings = block.nodes;

	      if (depth === 0 || siblings.length === 0) {
	        push({ type: 'text', value });
	        continue;
	      }

	      if (prev.type === 'dot') {
	        block.range = [];
	        prev.value += value;
	        prev.type = 'range';

	        if (block.nodes.length !== 3 && block.nodes.length !== 5) {
	          block.invalid = true;
	          block.ranges = 0;
	          prev.type = 'text';
	          continue;
	        }

	        block.ranges++;
	        block.args = [];
	        continue;
	      }

	      if (prev.type === 'range') {
	        siblings.pop();

	        const before = siblings[siblings.length - 1];
	        before.value += prev.value + value;
	        prev = before;
	        block.ranges--;
	        continue;
	      }

	      push({ type: 'dot', value });
	      continue;
	    }

	    /**
	     * Text
	     */

	    push({ type: 'text', value });
	  }

	  // Mark imbalanced braces and brackets as invalid
	  do {
	    block = stack.pop();

	    if (block.type !== 'root') {
	      block.nodes.forEach(node => {
	        if (!node.nodes) {
	          if (node.type === 'open') node.isOpen = true;
	          if (node.type === 'close') node.isClose = true;
	          if (!node.nodes) node.type = 'text';
	          node.invalid = true;
	        }
	      });

	      // get the location of the block on parent.nodes (block's siblings)
	      const parent = stack[stack.length - 1];
	      const index = parent.nodes.indexOf(block);
	      // replace the (invalid) block with it's nodes
	      parent.nodes.splice(index, 1, ...block.nodes);
	    }
	  } while (stack.length > 0);

	  push({ type: 'eos' });
	  return ast;
	};

	parse_1$1 = parse;
	return parse_1$1;
}

var braces_1;
var hasRequiredBraces;

function requireBraces () {
	if (hasRequiredBraces) return braces_1;
	hasRequiredBraces = 1;

	const stringify = /*@__PURE__*/ requireStringify();
	const compile = /*@__PURE__*/ requireCompile();
	const expand = /*@__PURE__*/ requireExpand();
	const parse = /*@__PURE__*/ requireParse$1();

	/**
	 * Expand the given pattern or create a regex-compatible string.
	 *
	 * ```js
	 * const braces = require('braces');
	 * console.log(braces('{a,b,c}', { compile: true })); //=> ['(a|b|c)']
	 * console.log(braces('{a,b,c}')); //=> ['a', 'b', 'c']
	 * ```
	 * @param {String} `str`
	 * @param {Object} `options`
	 * @return {String}
	 * @api public
	 */

	const braces = (input, options = {}) => {
	  let output = [];

	  if (Array.isArray(input)) {
	    for (const pattern of input) {
	      const result = braces.create(pattern, options);
	      if (Array.isArray(result)) {
	        output.push(...result);
	      } else {
	        output.push(result);
	      }
	    }
	  } else {
	    output = [].concat(braces.create(input, options));
	  }

	  if (options && options.expand === true && options.nodupes === true) {
	    output = [...new Set(output)];
	  }
	  return output;
	};

	/**
	 * Parse the given `str` with the given `options`.
	 *
	 * ```js
	 * // braces.parse(pattern, [, options]);
	 * const ast = braces.parse('a/{b,c}/d');
	 * console.log(ast);
	 * ```
	 * @param {String} pattern Brace pattern to parse
	 * @param {Object} options
	 * @return {Object} Returns an AST
	 * @api public
	 */

	braces.parse = (input, options = {}) => parse(input, options);

	/**
	 * Creates a braces string from an AST, or an AST node.
	 *
	 * ```js
	 * const braces = require('braces');
	 * let ast = braces.parse('foo/{a,b}/bar');
	 * console.log(stringify(ast.nodes[2])); //=> '{a,b}'
	 * ```
	 * @param {String} `input` Brace pattern or AST.
	 * @param {Object} `options`
	 * @return {Array} Returns an array of expanded values.
	 * @api public
	 */

	braces.stringify = (input, options = {}) => {
	  if (typeof input === 'string') {
	    return stringify(braces.parse(input, options), options);
	  }
	  return stringify(input, options);
	};

	/**
	 * Compiles a brace pattern into a regex-compatible, optimized string.
	 * This method is called by the main [braces](#braces) function by default.
	 *
	 * ```js
	 * const braces = require('braces');
	 * console.log(braces.compile('a/{b,c}/d'));
	 * //=> ['a/(b|c)/d']
	 * ```
	 * @param {String} `input` Brace pattern or AST.
	 * @param {Object} `options`
	 * @return {Array} Returns an array of expanded values.
	 * @api public
	 */

	braces.compile = (input, options = {}) => {
	  if (typeof input === 'string') {
	    input = braces.parse(input, options);
	  }
	  return compile(input, options);
	};

	/**
	 * Expands a brace pattern into an array. This method is called by the
	 * main [braces](#braces) function when `options.expand` is true. Before
	 * using this method it's recommended that you read the [performance notes](#performance))
	 * and advantages of using [.compile](#compile) instead.
	 *
	 * ```js
	 * const braces = require('braces');
	 * console.log(braces.expand('a/{b,c}/d'));
	 * //=> ['a/b/d', 'a/c/d'];
	 * ```
	 * @param {String} `pattern` Brace pattern
	 * @param {Object} `options`
	 * @return {Array} Returns an array of expanded values.
	 * @api public
	 */

	braces.expand = (input, options = {}) => {
	  if (typeof input === 'string') {
	    input = braces.parse(input, options);
	  }

	  let result = expand(input, options);

	  // filter out empty strings if specified
	  if (options.noempty === true) {
	    result = result.filter(Boolean);
	  }

	  // filter out duplicates if specified
	  if (options.nodupes === true) {
	    result = [...new Set(result)];
	  }

	  return result;
	};

	/**
	 * Processes a brace pattern and returns either an expanded array
	 * (if `options.expand` is true), a highly optimized regex-compatible string.
	 * This method is called by the main [braces](#braces) function.
	 *
	 * ```js
	 * const braces = require('braces');
	 * console.log(braces.create('user-{200..300}/project-{a,b,c}-{1..10}'))
	 * //=> 'user-(20[0-9]|2[1-9][0-9]|300)/project-(a|b|c)-([1-9]|10)'
	 * ```
	 * @param {String} `pattern` Brace pattern
	 * @param {Object} `options`
	 * @return {Array} Returns an array of expanded values.
	 * @api public
	 */

	braces.create = (input, options = {}) => {
	  if (input === '' || input.length < 3) {
	    return [input];
	  }

	  return options.expand !== true
	    ? braces.compile(input, options)
	    : braces.expand(input, options);
	};

	/**
	 * Expose "braces"
	 */

	braces_1 = braces;
	return braces_1;
}

var utils = {};

var constants;
var hasRequiredConstants;

function requireConstants () {
	if (hasRequiredConstants) return constants;
	hasRequiredConstants = 1;

	const path = require$$0$1;
	const WIN_SLASH = '\\\\/';
	const WIN_NO_SLASH = `[^${WIN_SLASH}]`;

	/**
	 * Posix glob regex
	 */

	const DOT_LITERAL = '\\.';
	const PLUS_LITERAL = '\\+';
	const QMARK_LITERAL = '\\?';
	const SLASH_LITERAL = '\\/';
	const ONE_CHAR = '(?=.)';
	const QMARK = '[^/]';
	const END_ANCHOR = `(?:${SLASH_LITERAL}|$)`;
	const START_ANCHOR = `(?:^|${SLASH_LITERAL})`;
	const DOTS_SLASH = `${DOT_LITERAL}{1,2}${END_ANCHOR}`;
	const NO_DOT = `(?!${DOT_LITERAL})`;
	const NO_DOTS = `(?!${START_ANCHOR}${DOTS_SLASH})`;
	const NO_DOT_SLASH = `(?!${DOT_LITERAL}{0,1}${END_ANCHOR})`;
	const NO_DOTS_SLASH = `(?!${DOTS_SLASH})`;
	const QMARK_NO_DOT = `[^.${SLASH_LITERAL}]`;
	const STAR = `${QMARK}*?`;

	const POSIX_CHARS = {
	  DOT_LITERAL,
	  PLUS_LITERAL,
	  QMARK_LITERAL,
	  SLASH_LITERAL,
	  ONE_CHAR,
	  QMARK,
	  END_ANCHOR,
	  DOTS_SLASH,
	  NO_DOT,
	  NO_DOTS,
	  NO_DOT_SLASH,
	  NO_DOTS_SLASH,
	  QMARK_NO_DOT,
	  STAR,
	  START_ANCHOR
	};

	/**
	 * Windows glob regex
	 */

	const WINDOWS_CHARS = {
	  ...POSIX_CHARS,

	  SLASH_LITERAL: `[${WIN_SLASH}]`,
	  QMARK: WIN_NO_SLASH,
	  STAR: `${WIN_NO_SLASH}*?`,
	  DOTS_SLASH: `${DOT_LITERAL}{1,2}(?:[${WIN_SLASH}]|$)`,
	  NO_DOT: `(?!${DOT_LITERAL})`,
	  NO_DOTS: `(?!(?:^|[${WIN_SLASH}])${DOT_LITERAL}{1,2}(?:[${WIN_SLASH}]|$))`,
	  NO_DOT_SLASH: `(?!${DOT_LITERAL}{0,1}(?:[${WIN_SLASH}]|$))`,
	  NO_DOTS_SLASH: `(?!${DOT_LITERAL}{1,2}(?:[${WIN_SLASH}]|$))`,
	  QMARK_NO_DOT: `[^.${WIN_SLASH}]`,
	  START_ANCHOR: `(?:^|[${WIN_SLASH}])`,
	  END_ANCHOR: `(?:[${WIN_SLASH}]|$)`
	};

	/**
	 * POSIX Bracket Regex
	 */

	const POSIX_REGEX_SOURCE = {
	  alnum: 'a-zA-Z0-9',
	  alpha: 'a-zA-Z',
	  ascii: '\\x00-\\x7F',
	  blank: ' \\t',
	  cntrl: '\\x00-\\x1F\\x7F',
	  digit: '0-9',
	  graph: '\\x21-\\x7E',
	  lower: 'a-z',
	  print: '\\x20-\\x7E ',
	  punct: '\\-!"#$%&\'()\\*+,./:;<=>?@[\\]^_`{|}~',
	  space: ' \\t\\r\\n\\v\\f',
	  upper: 'A-Z',
	  word: 'A-Za-z0-9_',
	  xdigit: 'A-Fa-f0-9'
	};

	constants = {
	  MAX_LENGTH: 1024 * 64,
	  POSIX_REGEX_SOURCE,

	  // regular expressions
	  REGEX_BACKSLASH: /\\(?![*+?^${}(|)[\]])/g,
	  REGEX_NON_SPECIAL_CHARS: /^[^@![\].,$*+?^{}()|\\/]+/,
	  REGEX_SPECIAL_CHARS: /[-*+?.^${}(|)[\]]/,
	  REGEX_SPECIAL_CHARS_BACKREF: /(\\?)((\W)(\3*))/g,
	  REGEX_SPECIAL_CHARS_GLOBAL: /([-*+?.^${}(|)[\]])/g,
	  REGEX_REMOVE_BACKSLASH: /(?:\[.*?[^\\]\]|\\(?=.))/g,

	  // Replace globs with equivalent patterns to reduce parsing time.
	  REPLACEMENTS: {
	    '***': '*',
	    '**/**': '**',
	    '**/**/**': '**'
	  },

	  // Digits
	  CHAR_0: 48, /* 0 */
	  CHAR_9: 57, /* 9 */

	  // Alphabet chars.
	  CHAR_UPPERCASE_A: 65, /* A */
	  CHAR_LOWERCASE_A: 97, /* a */
	  CHAR_UPPERCASE_Z: 90, /* Z */
	  CHAR_LOWERCASE_Z: 122, /* z */

	  CHAR_LEFT_PARENTHESES: 40, /* ( */
	  CHAR_RIGHT_PARENTHESES: 41, /* ) */

	  CHAR_ASTERISK: 42, /* * */

	  // Non-alphabetic chars.
	  CHAR_AMPERSAND: 38, /* & */
	  CHAR_AT: 64, /* @ */
	  CHAR_BACKWARD_SLASH: 92, /* \ */
	  CHAR_CARRIAGE_RETURN: 13, /* \r */
	  CHAR_CIRCUMFLEX_ACCENT: 94, /* ^ */
	  CHAR_COLON: 58, /* : */
	  CHAR_COMMA: 44, /* , */
	  CHAR_DOT: 46, /* . */
	  CHAR_DOUBLE_QUOTE: 34, /* " */
	  CHAR_EQUAL: 61, /* = */
	  CHAR_EXCLAMATION_MARK: 33, /* ! */
	  CHAR_FORM_FEED: 12, /* \f */
	  CHAR_FORWARD_SLASH: 47, /* / */
	  CHAR_GRAVE_ACCENT: 96, /* ` */
	  CHAR_HASH: 35, /* # */
	  CHAR_HYPHEN_MINUS: 45, /* - */
	  CHAR_LEFT_ANGLE_BRACKET: 60, /* < */
	  CHAR_LEFT_CURLY_BRACE: 123, /* { */
	  CHAR_LEFT_SQUARE_BRACKET: 91, /* [ */
	  CHAR_LINE_FEED: 10, /* \n */
	  CHAR_NO_BREAK_SPACE: 160, /* \u00A0 */
	  CHAR_PERCENT: 37, /* % */
	  CHAR_PLUS: 43, /* + */
	  CHAR_QUESTION_MARK: 63, /* ? */
	  CHAR_RIGHT_ANGLE_BRACKET: 62, /* > */
	  CHAR_RIGHT_CURLY_BRACE: 125, /* } */
	  CHAR_RIGHT_SQUARE_BRACKET: 93, /* ] */
	  CHAR_SEMICOLON: 59, /* ; */
	  CHAR_SINGLE_QUOTE: 39, /* ' */
	  CHAR_SPACE: 32, /*   */
	  CHAR_TAB: 9, /* \t */
	  CHAR_UNDERSCORE: 95, /* _ */
	  CHAR_VERTICAL_LINE: 124, /* | */
	  CHAR_ZERO_WIDTH_NOBREAK_SPACE: 65279, /* \uFEFF */

	  SEP: path.sep,

	  /**
	   * Create EXTGLOB_CHARS
	   */

	  extglobChars(chars) {
	    return {
	      '!': { type: 'negate', open: '(?:(?!(?:', close: `))${chars.STAR})` },
	      '?': { type: 'qmark', open: '(?:', close: ')?' },
	      '+': { type: 'plus', open: '(?:', close: ')+' },
	      '*': { type: 'star', open: '(?:', close: ')*' },
	      '@': { type: 'at', open: '(?:', close: ')' }
	    };
	  },

	  /**
	   * Create GLOB_CHARS
	   */

	  globChars(win32) {
	    return win32 === true ? WINDOWS_CHARS : POSIX_CHARS;
	  }
	};
	return constants;
}

var hasRequiredUtils;

function requireUtils () {
	if (hasRequiredUtils) return utils;
	hasRequiredUtils = 1;
	(function (exports) {

		const path = require$$0$1;
		const win32 = process.platform === 'win32';
		const {
		  REGEX_BACKSLASH,
		  REGEX_REMOVE_BACKSLASH,
		  REGEX_SPECIAL_CHARS,
		  REGEX_SPECIAL_CHARS_GLOBAL
		} = /*@__PURE__*/ requireConstants();

		exports.isObject = val => val !== null && typeof val === 'object' && !Array.isArray(val);
		exports.hasRegexChars = str => REGEX_SPECIAL_CHARS.test(str);
		exports.isRegexChar = str => str.length === 1 && exports.hasRegexChars(str);
		exports.escapeRegex = str => str.replace(REGEX_SPECIAL_CHARS_GLOBAL, '\\$1');
		exports.toPosixSlashes = str => str.replace(REGEX_BACKSLASH, '/');

		exports.removeBackslashes = str => {
		  return str.replace(REGEX_REMOVE_BACKSLASH, match => {
		    return match === '\\' ? '' : match;
		  });
		};

		exports.supportsLookbehinds = () => {
		  const segs = process.version.slice(1).split('.').map(Number);
		  if (segs.length === 3 && segs[0] >= 9 || (segs[0] === 8 && segs[1] >= 10)) {
		    return true;
		  }
		  return false;
		};

		exports.isWindows = options => {
		  if (options && typeof options.windows === 'boolean') {
		    return options.windows;
		  }
		  return win32 === true || path.sep === '\\';
		};

		exports.escapeLast = (input, char, lastIdx) => {
		  const idx = input.lastIndexOf(char, lastIdx);
		  if (idx === -1) return input;
		  if (input[idx - 1] === '\\') return exports.escapeLast(input, char, idx - 1);
		  return `${input.slice(0, idx)}\\${input.slice(idx)}`;
		};

		exports.removePrefix = (input, state = {}) => {
		  let output = input;
		  if (output.startsWith('./')) {
		    output = output.slice(2);
		    state.prefix = './';
		  }
		  return output;
		};

		exports.wrapOutput = (input, state = {}, options = {}) => {
		  const prepend = options.contains ? '' : '^';
		  const append = options.contains ? '' : '$';

		  let output = `${prepend}(?:${input})${append}`;
		  if (state.negated === true) {
		    output = `(?:^(?!${output}).*$)`;
		  }
		  return output;
		}; 
	} (utils));
	return utils;
}

var scan_1;
var hasRequiredScan;

function requireScan () {
	if (hasRequiredScan) return scan_1;
	hasRequiredScan = 1;

	const utils = /*@__PURE__*/ requireUtils();
	const {
	  CHAR_ASTERISK,             /* * */
	  CHAR_AT,                   /* @ */
	  CHAR_BACKWARD_SLASH,       /* \ */
	  CHAR_COMMA,                /* , */
	  CHAR_DOT,                  /* . */
	  CHAR_EXCLAMATION_MARK,     /* ! */
	  CHAR_FORWARD_SLASH,        /* / */
	  CHAR_LEFT_CURLY_BRACE,     /* { */
	  CHAR_LEFT_PARENTHESES,     /* ( */
	  CHAR_LEFT_SQUARE_BRACKET,  /* [ */
	  CHAR_PLUS,                 /* + */
	  CHAR_QUESTION_MARK,        /* ? */
	  CHAR_RIGHT_CURLY_BRACE,    /* } */
	  CHAR_RIGHT_PARENTHESES,    /* ) */
	  CHAR_RIGHT_SQUARE_BRACKET  /* ] */
	} = /*@__PURE__*/ requireConstants();

	const isPathSeparator = code => {
	  return code === CHAR_FORWARD_SLASH || code === CHAR_BACKWARD_SLASH;
	};

	const depth = token => {
	  if (token.isPrefix !== true) {
	    token.depth = token.isGlobstar ? Infinity : 1;
	  }
	};

	/**
	 * Quickly scans a glob pattern and returns an object with a handful of
	 * useful properties, like `isGlob`, `path` (the leading non-glob, if it exists),
	 * `glob` (the actual pattern), `negated` (true if the path starts with `!` but not
	 * with `!(`) and `negatedExtglob` (true if the path starts with `!(`).
	 *
	 * ```js
	 * const pm = require('picomatch');
	 * console.log(pm.scan('foo/bar/*.js'));
	 * { isGlob: true, input: 'foo/bar/*.js', base: 'foo/bar', glob: '*.js' }
	 * ```
	 * @param {String} `str`
	 * @param {Object} `options`
	 * @return {Object} Returns an object with tokens and regex source string.
	 * @api public
	 */

	const scan = (input, options) => {
	  const opts = options || {};

	  const length = input.length - 1;
	  const scanToEnd = opts.parts === true || opts.scanToEnd === true;
	  const slashes = [];
	  const tokens = [];
	  const parts = [];

	  let str = input;
	  let index = -1;
	  let start = 0;
	  let lastIndex = 0;
	  let isBrace = false;
	  let isBracket = false;
	  let isGlob = false;
	  let isExtglob = false;
	  let isGlobstar = false;
	  let braceEscaped = false;
	  let backslashes = false;
	  let negated = false;
	  let negatedExtglob = false;
	  let finished = false;
	  let braces = 0;
	  let prev;
	  let code;
	  let token = { value: '', depth: 0, isGlob: false };

	  const eos = () => index >= length;
	  const peek = () => str.charCodeAt(index + 1);
	  const advance = () => {
	    prev = code;
	    return str.charCodeAt(++index);
	  };

	  while (index < length) {
	    code = advance();
	    let next;

	    if (code === CHAR_BACKWARD_SLASH) {
	      backslashes = token.backslashes = true;
	      code = advance();

	      if (code === CHAR_LEFT_CURLY_BRACE) {
	        braceEscaped = true;
	      }
	      continue;
	    }

	    if (braceEscaped === true || code === CHAR_LEFT_CURLY_BRACE) {
	      braces++;

	      while (eos() !== true && (code = advance())) {
	        if (code === CHAR_BACKWARD_SLASH) {
	          backslashes = token.backslashes = true;
	          advance();
	          continue;
	        }

	        if (code === CHAR_LEFT_CURLY_BRACE) {
	          braces++;
	          continue;
	        }

	        if (braceEscaped !== true && code === CHAR_DOT && (code = advance()) === CHAR_DOT) {
	          isBrace = token.isBrace = true;
	          isGlob = token.isGlob = true;
	          finished = true;

	          if (scanToEnd === true) {
	            continue;
	          }

	          break;
	        }

	        if (braceEscaped !== true && code === CHAR_COMMA) {
	          isBrace = token.isBrace = true;
	          isGlob = token.isGlob = true;
	          finished = true;

	          if (scanToEnd === true) {
	            continue;
	          }

	          break;
	        }

	        if (code === CHAR_RIGHT_CURLY_BRACE) {
	          braces--;

	          if (braces === 0) {
	            braceEscaped = false;
	            isBrace = token.isBrace = true;
	            finished = true;
	            break;
	          }
	        }
	      }

	      if (scanToEnd === true) {
	        continue;
	      }

	      break;
	    }

	    if (code === CHAR_FORWARD_SLASH) {
	      slashes.push(index);
	      tokens.push(token);
	      token = { value: '', depth: 0, isGlob: false };

	      if (finished === true) continue;
	      if (prev === CHAR_DOT && index === (start + 1)) {
	        start += 2;
	        continue;
	      }

	      lastIndex = index + 1;
	      continue;
	    }

	    if (opts.noext !== true) {
	      const isExtglobChar = code === CHAR_PLUS
	        || code === CHAR_AT
	        || code === CHAR_ASTERISK
	        || code === CHAR_QUESTION_MARK
	        || code === CHAR_EXCLAMATION_MARK;

	      if (isExtglobChar === true && peek() === CHAR_LEFT_PARENTHESES) {
	        isGlob = token.isGlob = true;
	        isExtglob = token.isExtglob = true;
	        finished = true;
	        if (code === CHAR_EXCLAMATION_MARK && index === start) {
	          negatedExtglob = true;
	        }

	        if (scanToEnd === true) {
	          while (eos() !== true && (code = advance())) {
	            if (code === CHAR_BACKWARD_SLASH) {
	              backslashes = token.backslashes = true;
	              code = advance();
	              continue;
	            }

	            if (code === CHAR_RIGHT_PARENTHESES) {
	              isGlob = token.isGlob = true;
	              finished = true;
	              break;
	            }
	          }
	          continue;
	        }
	        break;
	      }
	    }

	    if (code === CHAR_ASTERISK) {
	      if (prev === CHAR_ASTERISK) isGlobstar = token.isGlobstar = true;
	      isGlob = token.isGlob = true;
	      finished = true;

	      if (scanToEnd === true) {
	        continue;
	      }
	      break;
	    }

	    if (code === CHAR_QUESTION_MARK) {
	      isGlob = token.isGlob = true;
	      finished = true;

	      if (scanToEnd === true) {
	        continue;
	      }
	      break;
	    }

	    if (code === CHAR_LEFT_SQUARE_BRACKET) {
	      while (eos() !== true && (next = advance())) {
	        if (next === CHAR_BACKWARD_SLASH) {
	          backslashes = token.backslashes = true;
	          advance();
	          continue;
	        }

	        if (next === CHAR_RIGHT_SQUARE_BRACKET) {
	          isBracket = token.isBracket = true;
	          isGlob = token.isGlob = true;
	          finished = true;
	          break;
	        }
	      }

	      if (scanToEnd === true) {
	        continue;
	      }

	      break;
	    }

	    if (opts.nonegate !== true && code === CHAR_EXCLAMATION_MARK && index === start) {
	      negated = token.negated = true;
	      start++;
	      continue;
	    }

	    if (opts.noparen !== true && code === CHAR_LEFT_PARENTHESES) {
	      isGlob = token.isGlob = true;

	      if (scanToEnd === true) {
	        while (eos() !== true && (code = advance())) {
	          if (code === CHAR_LEFT_PARENTHESES) {
	            backslashes = token.backslashes = true;
	            code = advance();
	            continue;
	          }

	          if (code === CHAR_RIGHT_PARENTHESES) {
	            finished = true;
	            break;
	          }
	        }
	        continue;
	      }
	      break;
	    }

	    if (isGlob === true) {
	      finished = true;

	      if (scanToEnd === true) {
	        continue;
	      }

	      break;
	    }
	  }

	  if (opts.noext === true) {
	    isExtglob = false;
	    isGlob = false;
	  }

	  let base = str;
	  let prefix = '';
	  let glob = '';

	  if (start > 0) {
	    prefix = str.slice(0, start);
	    str = str.slice(start);
	    lastIndex -= start;
	  }

	  if (base && isGlob === true && lastIndex > 0) {
	    base = str.slice(0, lastIndex);
	    glob = str.slice(lastIndex);
	  } else if (isGlob === true) {
	    base = '';
	    glob = str;
	  } else {
	    base = str;
	  }

	  if (base && base !== '' && base !== '/' && base !== str) {
	    if (isPathSeparator(base.charCodeAt(base.length - 1))) {
	      base = base.slice(0, -1);
	    }
	  }

	  if (opts.unescape === true) {
	    if (glob) glob = utils.removeBackslashes(glob);

	    if (base && backslashes === true) {
	      base = utils.removeBackslashes(base);
	    }
	  }

	  const state = {
	    prefix,
	    input,
	    start,
	    base,
	    glob,
	    isBrace,
	    isBracket,
	    isGlob,
	    isExtglob,
	    isGlobstar,
	    negated,
	    negatedExtglob
	  };

	  if (opts.tokens === true) {
	    state.maxDepth = 0;
	    if (!isPathSeparator(code)) {
	      tokens.push(token);
	    }
	    state.tokens = tokens;
	  }

	  if (opts.parts === true || opts.tokens === true) {
	    let prevIndex;

	    for (let idx = 0; idx < slashes.length; idx++) {
	      const n = prevIndex ? prevIndex + 1 : start;
	      const i = slashes[idx];
	      const value = input.slice(n, i);
	      if (opts.tokens) {
	        if (idx === 0 && start !== 0) {
	          tokens[idx].isPrefix = true;
	          tokens[idx].value = prefix;
	        } else {
	          tokens[idx].value = value;
	        }
	        depth(tokens[idx]);
	        state.maxDepth += tokens[idx].depth;
	      }
	      if (idx !== 0 || value !== '') {
	        parts.push(value);
	      }
	      prevIndex = i;
	    }

	    if (prevIndex && prevIndex + 1 < input.length) {
	      const value = input.slice(prevIndex + 1);
	      parts.push(value);

	      if (opts.tokens) {
	        tokens[tokens.length - 1].value = value;
	        depth(tokens[tokens.length - 1]);
	        state.maxDepth += tokens[tokens.length - 1].depth;
	      }
	    }

	    state.slashes = slashes;
	    state.parts = parts;
	  }

	  return state;
	};

	scan_1 = scan;
	return scan_1;
}

var parse_1;
var hasRequiredParse;

function requireParse () {
	if (hasRequiredParse) return parse_1;
	hasRequiredParse = 1;

	const constants = /*@__PURE__*/ requireConstants();
	const utils = /*@__PURE__*/ requireUtils();

	/**
	 * Constants
	 */

	const {
	  MAX_LENGTH,
	  POSIX_REGEX_SOURCE,
	  REGEX_NON_SPECIAL_CHARS,
	  REGEX_SPECIAL_CHARS_BACKREF,
	  REPLACEMENTS
	} = constants;

	/**
	 * Helpers
	 */

	const expandRange = (args, options) => {
	  if (typeof options.expandRange === 'function') {
	    return options.expandRange(...args, options);
	  }

	  args.sort();
	  const value = `[${args.join('-')}]`;

	  try {
	    /* eslint-disable-next-line no-new */
	    new RegExp(value);
	  } catch (ex) {
	    return args.map(v => utils.escapeRegex(v)).join('..');
	  }

	  return value;
	};

	/**
	 * Create the message for a syntax error
	 */

	const syntaxError = (type, char) => {
	  return `Missing ${type}: "${char}" - use "\\\\${char}" to match literal characters`;
	};

	/**
	 * Parse the given input string.
	 * @param {String} input
	 * @param {Object} options
	 * @return {Object}
	 */

	const parse = (input, options) => {
	  if (typeof input !== 'string') {
	    throw new TypeError('Expected a string');
	  }

	  input = REPLACEMENTS[input] || input;

	  const opts = { ...options };
	  const max = typeof opts.maxLength === 'number' ? Math.min(MAX_LENGTH, opts.maxLength) : MAX_LENGTH;

	  let len = input.length;
	  if (len > max) {
	    throw new SyntaxError(`Input length: ${len}, exceeds maximum allowed length: ${max}`);
	  }

	  const bos = { type: 'bos', value: '', output: opts.prepend || '' };
	  const tokens = [bos];

	  const capture = opts.capture ? '' : '?:';
	  const win32 = utils.isWindows(options);

	  // create constants based on platform, for windows or posix
	  const PLATFORM_CHARS = constants.globChars(win32);
	  const EXTGLOB_CHARS = constants.extglobChars(PLATFORM_CHARS);

	  const {
	    DOT_LITERAL,
	    PLUS_LITERAL,
	    SLASH_LITERAL,
	    ONE_CHAR,
	    DOTS_SLASH,
	    NO_DOT,
	    NO_DOT_SLASH,
	    NO_DOTS_SLASH,
	    QMARK,
	    QMARK_NO_DOT,
	    STAR,
	    START_ANCHOR
	  } = PLATFORM_CHARS;

	  const globstar = opts => {
	    return `(${capture}(?:(?!${START_ANCHOR}${opts.dot ? DOTS_SLASH : DOT_LITERAL}).)*?)`;
	  };

	  const nodot = opts.dot ? '' : NO_DOT;
	  const qmarkNoDot = opts.dot ? QMARK : QMARK_NO_DOT;
	  let star = opts.bash === true ? globstar(opts) : STAR;

	  if (opts.capture) {
	    star = `(${star})`;
	  }

	  // minimatch options support
	  if (typeof opts.noext === 'boolean') {
	    opts.noextglob = opts.noext;
	  }

	  const state = {
	    input,
	    index: -1,
	    start: 0,
	    dot: opts.dot === true,
	    consumed: '',
	    output: '',
	    prefix: '',
	    backtrack: false,
	    negated: false,
	    brackets: 0,
	    braces: 0,
	    parens: 0,
	    quotes: 0,
	    globstar: false,
	    tokens
	  };

	  input = utils.removePrefix(input, state);
	  len = input.length;

	  const extglobs = [];
	  const braces = [];
	  const stack = [];
	  let prev = bos;
	  let value;

	  /**
	   * Tokenizing helpers
	   */

	  const eos = () => state.index === len - 1;
	  const peek = state.peek = (n = 1) => input[state.index + n];
	  const advance = state.advance = () => input[++state.index] || '';
	  const remaining = () => input.slice(state.index + 1);
	  const consume = (value = '', num = 0) => {
	    state.consumed += value;
	    state.index += num;
	  };

	  const append = token => {
	    state.output += token.output != null ? token.output : token.value;
	    consume(token.value);
	  };

	  const negate = () => {
	    let count = 1;

	    while (peek() === '!' && (peek(2) !== '(' || peek(3) === '?')) {
	      advance();
	      state.start++;
	      count++;
	    }

	    if (count % 2 === 0) {
	      return false;
	    }

	    state.negated = true;
	    state.start++;
	    return true;
	  };

	  const increment = type => {
	    state[type]++;
	    stack.push(type);
	  };

	  const decrement = type => {
	    state[type]--;
	    stack.pop();
	  };

	  /**
	   * Push tokens onto the tokens array. This helper speeds up
	   * tokenizing by 1) helping us avoid backtracking as much as possible,
	   * and 2) helping us avoid creating extra tokens when consecutive
	   * characters are plain text. This improves performance and simplifies
	   * lookbehinds.
	   */

	  const push = tok => {
	    if (prev.type === 'globstar') {
	      const isBrace = state.braces > 0 && (tok.type === 'comma' || tok.type === 'brace');
	      const isExtglob = tok.extglob === true || (extglobs.length && (tok.type === 'pipe' || tok.type === 'paren'));

	      if (tok.type !== 'slash' && tok.type !== 'paren' && !isBrace && !isExtglob) {
	        state.output = state.output.slice(0, -prev.output.length);
	        prev.type = 'star';
	        prev.value = '*';
	        prev.output = star;
	        state.output += prev.output;
	      }
	    }

	    if (extglobs.length && tok.type !== 'paren') {
	      extglobs[extglobs.length - 1].inner += tok.value;
	    }

	    if (tok.value || tok.output) append(tok);
	    if (prev && prev.type === 'text' && tok.type === 'text') {
	      prev.value += tok.value;
	      prev.output = (prev.output || '') + tok.value;
	      return;
	    }

	    tok.prev = prev;
	    tokens.push(tok);
	    prev = tok;
	  };

	  const extglobOpen = (type, value) => {
	    const token = { ...EXTGLOB_CHARS[value], conditions: 1, inner: '' };

	    token.prev = prev;
	    token.parens = state.parens;
	    token.output = state.output;
	    const output = (opts.capture ? '(' : '') + token.open;

	    increment('parens');
	    push({ type, value, output: state.output ? '' : ONE_CHAR });
	    push({ type: 'paren', extglob: true, value: advance(), output });
	    extglobs.push(token);
	  };

	  const extglobClose = token => {
	    let output = token.close + (opts.capture ? ')' : '');
	    let rest;

	    if (token.type === 'negate') {
	      let extglobStar = star;

	      if (token.inner && token.inner.length > 1 && token.inner.includes('/')) {
	        extglobStar = globstar(opts);
	      }

	      if (extglobStar !== star || eos() || /^\)+$/.test(remaining())) {
	        output = token.close = `)$))${extglobStar}`;
	      }

	      if (token.inner.includes('*') && (rest = remaining()) && /^\.[^\\/.]+$/.test(rest)) {
	        // Any non-magical string (`.ts`) or even nested expression (`.{ts,tsx}`) can follow after the closing parenthesis.
	        // In this case, we need to parse the string and use it in the output of the original pattern.
	        // Suitable patterns: `/!(*.d).ts`, `/!(*.d).{ts,tsx}`, `**/!(*-dbg).@(js)`.
	        //
	        // Disabling the `fastpaths` option due to a problem with parsing strings as `.ts` in the pattern like `**/!(*.d).ts`.
	        const expression = parse(rest, { ...options, fastpaths: false }).output;

	        output = token.close = `)${expression})${extglobStar})`;
	      }

	      if (token.prev.type === 'bos') {
	        state.negatedExtglob = true;
	      }
	    }

	    push({ type: 'paren', extglob: true, value, output });
	    decrement('parens');
	  };

	  /**
	   * Fast paths
	   */

	  if (opts.fastpaths !== false && !/(^[*!]|[/()[\]{}"])/.test(input)) {
	    let backslashes = false;

	    let output = input.replace(REGEX_SPECIAL_CHARS_BACKREF, (m, esc, chars, first, rest, index) => {
	      if (first === '\\') {
	        backslashes = true;
	        return m;
	      }

	      if (first === '?') {
	        if (esc) {
	          return esc + first + (rest ? QMARK.repeat(rest.length) : '');
	        }
	        if (index === 0) {
	          return qmarkNoDot + (rest ? QMARK.repeat(rest.length) : '');
	        }
	        return QMARK.repeat(chars.length);
	      }

	      if (first === '.') {
	        return DOT_LITERAL.repeat(chars.length);
	      }

	      if (first === '*') {
	        if (esc) {
	          return esc + first + (rest ? star : '');
	        }
	        return star;
	      }
	      return esc ? m : `\\${m}`;
	    });

	    if (backslashes === true) {
	      if (opts.unescape === true) {
	        output = output.replace(/\\/g, '');
	      } else {
	        output = output.replace(/\\+/g, m => {
	          return m.length % 2 === 0 ? '\\\\' : (m ? '\\' : '');
	        });
	      }
	    }

	    if (output === input && opts.contains === true) {
	      state.output = input;
	      return state;
	    }

	    state.output = utils.wrapOutput(output, state, options);
	    return state;
	  }

	  /**
	   * Tokenize input until we reach end-of-string
	   */

	  while (!eos()) {
	    value = advance();

	    if (value === '\u0000') {
	      continue;
	    }

	    /**
	     * Escaped characters
	     */

	    if (value === '\\') {
	      const next = peek();

	      if (next === '/' && opts.bash !== true) {
	        continue;
	      }

	      if (next === '.' || next === ';') {
	        continue;
	      }

	      if (!next) {
	        value += '\\';
	        push({ type: 'text', value });
	        continue;
	      }

	      // collapse slashes to reduce potential for exploits
	      const match = /^\\+/.exec(remaining());
	      let slashes = 0;

	      if (match && match[0].length > 2) {
	        slashes = match[0].length;
	        state.index += slashes;
	        if (slashes % 2 !== 0) {
	          value += '\\';
	        }
	      }

	      if (opts.unescape === true) {
	        value = advance();
	      } else {
	        value += advance();
	      }

	      if (state.brackets === 0) {
	        push({ type: 'text', value });
	        continue;
	      }
	    }

	    /**
	     * If we're inside a regex character class, continue
	     * until we reach the closing bracket.
	     */

	    if (state.brackets > 0 && (value !== ']' || prev.value === '[' || prev.value === '[^')) {
	      if (opts.posix !== false && value === ':') {
	        const inner = prev.value.slice(1);
	        if (inner.includes('[')) {
	          prev.posix = true;

	          if (inner.includes(':')) {
	            const idx = prev.value.lastIndexOf('[');
	            const pre = prev.value.slice(0, idx);
	            const rest = prev.value.slice(idx + 2);
	            const posix = POSIX_REGEX_SOURCE[rest];
	            if (posix) {
	              prev.value = pre + posix;
	              state.backtrack = true;
	              advance();

	              if (!bos.output && tokens.indexOf(prev) === 1) {
	                bos.output = ONE_CHAR;
	              }
	              continue;
	            }
	          }
	        }
	      }

	      if ((value === '[' && peek() !== ':') || (value === '-' && peek() === ']')) {
	        value = `\\${value}`;
	      }

	      if (value === ']' && (prev.value === '[' || prev.value === '[^')) {
	        value = `\\${value}`;
	      }

	      if (opts.posix === true && value === '!' && prev.value === '[') {
	        value = '^';
	      }

	      prev.value += value;
	      append({ value });
	      continue;
	    }

	    /**
	     * If we're inside a quoted string, continue
	     * until we reach the closing double quote.
	     */

	    if (state.quotes === 1 && value !== '"') {
	      value = utils.escapeRegex(value);
	      prev.value += value;
	      append({ value });
	      continue;
	    }

	    /**
	     * Double quotes
	     */

	    if (value === '"') {
	      state.quotes = state.quotes === 1 ? 0 : 1;
	      if (opts.keepQuotes === true) {
	        push({ type: 'text', value });
	      }
	      continue;
	    }

	    /**
	     * Parentheses
	     */

	    if (value === '(') {
	      increment('parens');
	      push({ type: 'paren', value });
	      continue;
	    }

	    if (value === ')') {
	      if (state.parens === 0 && opts.strictBrackets === true) {
	        throw new SyntaxError(syntaxError('opening', '('));
	      }

	      const extglob = extglobs[extglobs.length - 1];
	      if (extglob && state.parens === extglob.parens + 1) {
	        extglobClose(extglobs.pop());
	        continue;
	      }

	      push({ type: 'paren', value, output: state.parens ? ')' : '\\)' });
	      decrement('parens');
	      continue;
	    }

	    /**
	     * Square brackets
	     */

	    if (value === '[') {
	      if (opts.nobracket === true || !remaining().includes(']')) {
	        if (opts.nobracket !== true && opts.strictBrackets === true) {
	          throw new SyntaxError(syntaxError('closing', ']'));
	        }

	        value = `\\${value}`;
	      } else {
	        increment('brackets');
	      }

	      push({ type: 'bracket', value });
	      continue;
	    }

	    if (value === ']') {
	      if (opts.nobracket === true || (prev && prev.type === 'bracket' && prev.value.length === 1)) {
	        push({ type: 'text', value, output: `\\${value}` });
	        continue;
	      }

	      if (state.brackets === 0) {
	        if (opts.strictBrackets === true) {
	          throw new SyntaxError(syntaxError('opening', '['));
	        }

	        push({ type: 'text', value, output: `\\${value}` });
	        continue;
	      }

	      decrement('brackets');

	      const prevValue = prev.value.slice(1);
	      if (prev.posix !== true && prevValue[0] === '^' && !prevValue.includes('/')) {
	        value = `/${value}`;
	      }

	      prev.value += value;
	      append({ value });

	      // when literal brackets are explicitly disabled
	      // assume we should match with a regex character class
	      if (opts.literalBrackets === false || utils.hasRegexChars(prevValue)) {
	        continue;
	      }

	      const escaped = utils.escapeRegex(prev.value);
	      state.output = state.output.slice(0, -prev.value.length);

	      // when literal brackets are explicitly enabled
	      // assume we should escape the brackets to match literal characters
	      if (opts.literalBrackets === true) {
	        state.output += escaped;
	        prev.value = escaped;
	        continue;
	      }

	      // when the user specifies nothing, try to match both
	      prev.value = `(${capture}${escaped}|${prev.value})`;
	      state.output += prev.value;
	      continue;
	    }

	    /**
	     * Braces
	     */

	    if (value === '{' && opts.nobrace !== true) {
	      increment('braces');

	      const open = {
	        type: 'brace',
	        value,
	        output: '(',
	        outputIndex: state.output.length,
	        tokensIndex: state.tokens.length
	      };

	      braces.push(open);
	      push(open);
	      continue;
	    }

	    if (value === '}') {
	      const brace = braces[braces.length - 1];

	      if (opts.nobrace === true || !brace) {
	        push({ type: 'text', value, output: value });
	        continue;
	      }

	      let output = ')';

	      if (brace.dots === true) {
	        const arr = tokens.slice();
	        const range = [];

	        for (let i = arr.length - 1; i >= 0; i--) {
	          tokens.pop();
	          if (arr[i].type === 'brace') {
	            break;
	          }
	          if (arr[i].type !== 'dots') {
	            range.unshift(arr[i].value);
	          }
	        }

	        output = expandRange(range, opts);
	        state.backtrack = true;
	      }

	      if (brace.comma !== true && brace.dots !== true) {
	        const out = state.output.slice(0, brace.outputIndex);
	        const toks = state.tokens.slice(brace.tokensIndex);
	        brace.value = brace.output = '\\{';
	        value = output = '\\}';
	        state.output = out;
	        for (const t of toks) {
	          state.output += (t.output || t.value);
	        }
	      }

	      push({ type: 'brace', value, output });
	      decrement('braces');
	      braces.pop();
	      continue;
	    }

	    /**
	     * Pipes
	     */

	    if (value === '|') {
	      if (extglobs.length > 0) {
	        extglobs[extglobs.length - 1].conditions++;
	      }
	      push({ type: 'text', value });
	      continue;
	    }

	    /**
	     * Commas
	     */

	    if (value === ',') {
	      let output = value;

	      const brace = braces[braces.length - 1];
	      if (brace && stack[stack.length - 1] === 'braces') {
	        brace.comma = true;
	        output = '|';
	      }

	      push({ type: 'comma', value, output });
	      continue;
	    }

	    /**
	     * Slashes
	     */

	    if (value === '/') {
	      // if the beginning of the glob is "./", advance the start
	      // to the current index, and don't add the "./" characters
	      // to the state. This greatly simplifies lookbehinds when
	      // checking for BOS characters like "!" and "." (not "./")
	      if (prev.type === 'dot' && state.index === state.start + 1) {
	        state.start = state.index + 1;
	        state.consumed = '';
	        state.output = '';
	        tokens.pop();
	        prev = bos; // reset "prev" to the first token
	        continue;
	      }

	      push({ type: 'slash', value, output: SLASH_LITERAL });
	      continue;
	    }

	    /**
	     * Dots
	     */

	    if (value === '.') {
	      if (state.braces > 0 && prev.type === 'dot') {
	        if (prev.value === '.') prev.output = DOT_LITERAL;
	        const brace = braces[braces.length - 1];
	        prev.type = 'dots';
	        prev.output += value;
	        prev.value += value;
	        brace.dots = true;
	        continue;
	      }

	      if ((state.braces + state.parens) === 0 && prev.type !== 'bos' && prev.type !== 'slash') {
	        push({ type: 'text', value, output: DOT_LITERAL });
	        continue;
	      }

	      push({ type: 'dot', value, output: DOT_LITERAL });
	      continue;
	    }

	    /**
	     * Question marks
	     */

	    if (value === '?') {
	      const isGroup = prev && prev.value === '(';
	      if (!isGroup && opts.noextglob !== true && peek() === '(' && peek(2) !== '?') {
	        extglobOpen('qmark', value);
	        continue;
	      }

	      if (prev && prev.type === 'paren') {
	        const next = peek();
	        let output = value;

	        if (next === '<' && !utils.supportsLookbehinds()) {
	          throw new Error('Node.js v10 or higher is required for regex lookbehinds');
	        }

	        if ((prev.value === '(' && !/[!=<:]/.test(next)) || (next === '<' && !/<([!=]|\w+>)/.test(remaining()))) {
	          output = `\\${value}`;
	        }

	        push({ type: 'text', value, output });
	        continue;
	      }

	      if (opts.dot !== true && (prev.type === 'slash' || prev.type === 'bos')) {
	        push({ type: 'qmark', value, output: QMARK_NO_DOT });
	        continue;
	      }

	      push({ type: 'qmark', value, output: QMARK });
	      continue;
	    }

	    /**
	     * Exclamation
	     */

	    if (value === '!') {
	      if (opts.noextglob !== true && peek() === '(') {
	        if (peek(2) !== '?' || !/[!=<:]/.test(peek(3))) {
	          extglobOpen('negate', value);
	          continue;
	        }
	      }

	      if (opts.nonegate !== true && state.index === 0) {
	        negate();
	        continue;
	      }
	    }

	    /**
	     * Plus
	     */

	    if (value === '+') {
	      if (opts.noextglob !== true && peek() === '(' && peek(2) !== '?') {
	        extglobOpen('plus', value);
	        continue;
	      }

	      if ((prev && prev.value === '(') || opts.regex === false) {
	        push({ type: 'plus', value, output: PLUS_LITERAL });
	        continue;
	      }

	      if ((prev && (prev.type === 'bracket' || prev.type === 'paren' || prev.type === 'brace')) || state.parens > 0) {
	        push({ type: 'plus', value });
	        continue;
	      }

	      push({ type: 'plus', value: PLUS_LITERAL });
	      continue;
	    }

	    /**
	     * Plain text
	     */

	    if (value === '@') {
	      if (opts.noextglob !== true && peek() === '(' && peek(2) !== '?') {
	        push({ type: 'at', extglob: true, value, output: '' });
	        continue;
	      }

	      push({ type: 'text', value });
	      continue;
	    }

	    /**
	     * Plain text
	     */

	    if (value !== '*') {
	      if (value === '$' || value === '^') {
	        value = `\\${value}`;
	      }

	      const match = REGEX_NON_SPECIAL_CHARS.exec(remaining());
	      if (match) {
	        value += match[0];
	        state.index += match[0].length;
	      }

	      push({ type: 'text', value });
	      continue;
	    }

	    /**
	     * Stars
	     */

	    if (prev && (prev.type === 'globstar' || prev.star === true)) {
	      prev.type = 'star';
	      prev.star = true;
	      prev.value += value;
	      prev.output = star;
	      state.backtrack = true;
	      state.globstar = true;
	      consume(value);
	      continue;
	    }

	    let rest = remaining();
	    if (opts.noextglob !== true && /^\([^?]/.test(rest)) {
	      extglobOpen('star', value);
	      continue;
	    }

	    if (prev.type === 'star') {
	      if (opts.noglobstar === true) {
	        consume(value);
	        continue;
	      }

	      const prior = prev.prev;
	      const before = prior.prev;
	      const isStart = prior.type === 'slash' || prior.type === 'bos';
	      const afterStar = before && (before.type === 'star' || before.type === 'globstar');

	      if (opts.bash === true && (!isStart || (rest[0] && rest[0] !== '/'))) {
	        push({ type: 'star', value, output: '' });
	        continue;
	      }

	      const isBrace = state.braces > 0 && (prior.type === 'comma' || prior.type === 'brace');
	      const isExtglob = extglobs.length && (prior.type === 'pipe' || prior.type === 'paren');
	      if (!isStart && prior.type !== 'paren' && !isBrace && !isExtglob) {
	        push({ type: 'star', value, output: '' });
	        continue;
	      }

	      // strip consecutive `/**/`
	      while (rest.slice(0, 3) === '/**') {
	        const after = input[state.index + 4];
	        if (after && after !== '/') {
	          break;
	        }
	        rest = rest.slice(3);
	        consume('/**', 3);
	      }

	      if (prior.type === 'bos' && eos()) {
	        prev.type = 'globstar';
	        prev.value += value;
	        prev.output = globstar(opts);
	        state.output = prev.output;
	        state.globstar = true;
	        consume(value);
	        continue;
	      }

	      if (prior.type === 'slash' && prior.prev.type !== 'bos' && !afterStar && eos()) {
	        state.output = state.output.slice(0, -(prior.output + prev.output).length);
	        prior.output = `(?:${prior.output}`;

	        prev.type = 'globstar';
	        prev.output = globstar(opts) + (opts.strictSlashes ? ')' : '|$)');
	        prev.value += value;
	        state.globstar = true;
	        state.output += prior.output + prev.output;
	        consume(value);
	        continue;
	      }

	      if (prior.type === 'slash' && prior.prev.type !== 'bos' && rest[0] === '/') {
	        const end = rest[1] !== void 0 ? '|$' : '';

	        state.output = state.output.slice(0, -(prior.output + prev.output).length);
	        prior.output = `(?:${prior.output}`;

	        prev.type = 'globstar';
	        prev.output = `${globstar(opts)}${SLASH_LITERAL}|${SLASH_LITERAL}${end})`;
	        prev.value += value;

	        state.output += prior.output + prev.output;
	        state.globstar = true;

	        consume(value + advance());

	        push({ type: 'slash', value: '/', output: '' });
	        continue;
	      }

	      if (prior.type === 'bos' && rest[0] === '/') {
	        prev.type = 'globstar';
	        prev.value += value;
	        prev.output = `(?:^|${SLASH_LITERAL}|${globstar(opts)}${SLASH_LITERAL})`;
	        state.output = prev.output;
	        state.globstar = true;
	        consume(value + advance());
	        push({ type: 'slash', value: '/', output: '' });
	        continue;
	      }

	      // remove single star from output
	      state.output = state.output.slice(0, -prev.output.length);

	      // reset previous token to globstar
	      prev.type = 'globstar';
	      prev.output = globstar(opts);
	      prev.value += value;

	      // reset output with globstar
	      state.output += prev.output;
	      state.globstar = true;
	      consume(value);
	      continue;
	    }

	    const token = { type: 'star', value, output: star };

	    if (opts.bash === true) {
	      token.output = '.*?';
	      if (prev.type === 'bos' || prev.type === 'slash') {
	        token.output = nodot + token.output;
	      }
	      push(token);
	      continue;
	    }

	    if (prev && (prev.type === 'bracket' || prev.type === 'paren') && opts.regex === true) {
	      token.output = value;
	      push(token);
	      continue;
	    }

	    if (state.index === state.start || prev.type === 'slash' || prev.type === 'dot') {
	      if (prev.type === 'dot') {
	        state.output += NO_DOT_SLASH;
	        prev.output += NO_DOT_SLASH;

	      } else if (opts.dot === true) {
	        state.output += NO_DOTS_SLASH;
	        prev.output += NO_DOTS_SLASH;

	      } else {
	        state.output += nodot;
	        prev.output += nodot;
	      }

	      if (peek() !== '*') {
	        state.output += ONE_CHAR;
	        prev.output += ONE_CHAR;
	      }
	    }

	    push(token);
	  }

	  while (state.brackets > 0) {
	    if (opts.strictBrackets === true) throw new SyntaxError(syntaxError('closing', ']'));
	    state.output = utils.escapeLast(state.output, '[');
	    decrement('brackets');
	  }

	  while (state.parens > 0) {
	    if (opts.strictBrackets === true) throw new SyntaxError(syntaxError('closing', ')'));
	    state.output = utils.escapeLast(state.output, '(');
	    decrement('parens');
	  }

	  while (state.braces > 0) {
	    if (opts.strictBrackets === true) throw new SyntaxError(syntaxError('closing', '}'));
	    state.output = utils.escapeLast(state.output, '{');
	    decrement('braces');
	  }

	  if (opts.strictSlashes !== true && (prev.type === 'star' || prev.type === 'bracket')) {
	    push({ type: 'maybe_slash', value: '', output: `${SLASH_LITERAL}?` });
	  }

	  // rebuild the output if we had to backtrack at any point
	  if (state.backtrack === true) {
	    state.output = '';

	    for (const token of state.tokens) {
	      state.output += token.output != null ? token.output : token.value;

	      if (token.suffix) {
	        state.output += token.suffix;
	      }
	    }
	  }

	  return state;
	};

	/**
	 * Fast paths for creating regular expressions for common glob patterns.
	 * This can significantly speed up processing and has very little downside
	 * impact when none of the fast paths match.
	 */

	parse.fastpaths = (input, options) => {
	  const opts = { ...options };
	  const max = typeof opts.maxLength === 'number' ? Math.min(MAX_LENGTH, opts.maxLength) : MAX_LENGTH;
	  const len = input.length;
	  if (len > max) {
	    throw new SyntaxError(`Input length: ${len}, exceeds maximum allowed length: ${max}`);
	  }

	  input = REPLACEMENTS[input] || input;
	  const win32 = utils.isWindows(options);

	  // create constants based on platform, for windows or posix
	  const {
	    DOT_LITERAL,
	    SLASH_LITERAL,
	    ONE_CHAR,
	    DOTS_SLASH,
	    NO_DOT,
	    NO_DOTS,
	    NO_DOTS_SLASH,
	    STAR,
	    START_ANCHOR
	  } = constants.globChars(win32);

	  const nodot = opts.dot ? NO_DOTS : NO_DOT;
	  const slashDot = opts.dot ? NO_DOTS_SLASH : NO_DOT;
	  const capture = opts.capture ? '' : '?:';
	  const state = { negated: false, prefix: '' };
	  let star = opts.bash === true ? '.*?' : STAR;

	  if (opts.capture) {
	    star = `(${star})`;
	  }

	  const globstar = opts => {
	    if (opts.noglobstar === true) return star;
	    return `(${capture}(?:(?!${START_ANCHOR}${opts.dot ? DOTS_SLASH : DOT_LITERAL}).)*?)`;
	  };

	  const create = str => {
	    switch (str) {
	      case '*':
	        return `${nodot}${ONE_CHAR}${star}`;

	      case '.*':
	        return `${DOT_LITERAL}${ONE_CHAR}${star}`;

	      case '*.*':
	        return `${nodot}${star}${DOT_LITERAL}${ONE_CHAR}${star}`;

	      case '*/*':
	        return `${nodot}${star}${SLASH_LITERAL}${ONE_CHAR}${slashDot}${star}`;

	      case '**':
	        return nodot + globstar(opts);

	      case '**/*':
	        return `(?:${nodot}${globstar(opts)}${SLASH_LITERAL})?${slashDot}${ONE_CHAR}${star}`;

	      case '**/*.*':
	        return `(?:${nodot}${globstar(opts)}${SLASH_LITERAL})?${slashDot}${star}${DOT_LITERAL}${ONE_CHAR}${star}`;

	      case '**/.*':
	        return `(?:${nodot}${globstar(opts)}${SLASH_LITERAL})?${DOT_LITERAL}${ONE_CHAR}${star}`;

	      default: {
	        const match = /^(.*?)\.(\w+)$/.exec(str);
	        if (!match) return;

	        const source = create(match[1]);
	        if (!source) return;

	        return source + DOT_LITERAL + match[2];
	      }
	    }
	  };

	  const output = utils.removePrefix(input, state);
	  let source = create(output);

	  if (source && opts.strictSlashes !== true) {
	    source += `${SLASH_LITERAL}?`;
	  }

	  return source;
	};

	parse_1 = parse;
	return parse_1;
}

var picomatch_1;
var hasRequiredPicomatch$1;

function requirePicomatch$1 () {
	if (hasRequiredPicomatch$1) return picomatch_1;
	hasRequiredPicomatch$1 = 1;

	const path = require$$0$1;
	const scan = /*@__PURE__*/ requireScan();
	const parse = /*@__PURE__*/ requireParse();
	const utils = /*@__PURE__*/ requireUtils();
	const constants = /*@__PURE__*/ requireConstants();
	const isObject = val => val && typeof val === 'object' && !Array.isArray(val);

	/**
	 * Creates a matcher function from one or more glob patterns. The
	 * returned function takes a string to match as its first argument,
	 * and returns true if the string is a match. The returned matcher
	 * function also takes a boolean as the second argument that, when true,
	 * returns an object with additional information.
	 *
	 * ```js
	 * const picomatch = require('picomatch');
	 * // picomatch(glob[, options]);
	 *
	 * const isMatch = picomatch('*.!(*a)');
	 * console.log(isMatch('a.a')); //=> false
	 * console.log(isMatch('a.b')); //=> true
	 * ```
	 * @name picomatch
	 * @param {String|Array} `globs` One or more glob patterns.
	 * @param {Object=} `options`
	 * @return {Function=} Returns a matcher function.
	 * @api public
	 */

	const picomatch = (glob, options, returnState = false) => {
	  if (Array.isArray(glob)) {
	    const fns = glob.map(input => picomatch(input, options, returnState));
	    const arrayMatcher = str => {
	      for (const isMatch of fns) {
	        const state = isMatch(str);
	        if (state) return state;
	      }
	      return false;
	    };
	    return arrayMatcher;
	  }

	  const isState = isObject(glob) && glob.tokens && glob.input;

	  if (glob === '' || (typeof glob !== 'string' && !isState)) {
	    throw new TypeError('Expected pattern to be a non-empty string');
	  }

	  const opts = options || {};
	  const posix = utils.isWindows(options);
	  const regex = isState
	    ? picomatch.compileRe(glob, options)
	    : picomatch.makeRe(glob, options, false, true);

	  const state = regex.state;
	  delete regex.state;

	  let isIgnored = () => false;
	  if (opts.ignore) {
	    const ignoreOpts = { ...options, ignore: null, onMatch: null, onResult: null };
	    isIgnored = picomatch(opts.ignore, ignoreOpts, returnState);
	  }

	  const matcher = (input, returnObject = false) => {
	    const { isMatch, match, output } = picomatch.test(input, regex, options, { glob, posix });
	    const result = { glob, state, regex, posix, input, output, match, isMatch };

	    if (typeof opts.onResult === 'function') {
	      opts.onResult(result);
	    }

	    if (isMatch === false) {
	      result.isMatch = false;
	      return returnObject ? result : false;
	    }

	    if (isIgnored(input)) {
	      if (typeof opts.onIgnore === 'function') {
	        opts.onIgnore(result);
	      }
	      result.isMatch = false;
	      return returnObject ? result : false;
	    }

	    if (typeof opts.onMatch === 'function') {
	      opts.onMatch(result);
	    }
	    return returnObject ? result : true;
	  };

	  if (returnState) {
	    matcher.state = state;
	  }

	  return matcher;
	};

	/**
	 * Test `input` with the given `regex`. This is used by the main
	 * `picomatch()` function to test the input string.
	 *
	 * ```js
	 * const picomatch = require('picomatch');
	 * // picomatch.test(input, regex[, options]);
	 *
	 * console.log(picomatch.test('foo/bar', /^(?:([^/]*?)\/([^/]*?))$/));
	 * // { isMatch: true, match: [ 'foo/', 'foo', 'bar' ], output: 'foo/bar' }
	 * ```
	 * @param {String} `input` String to test.
	 * @param {RegExp} `regex`
	 * @return {Object} Returns an object with matching info.
	 * @api public
	 */

	picomatch.test = (input, regex, options, { glob, posix } = {}) => {
	  if (typeof input !== 'string') {
	    throw new TypeError('Expected input to be a string');
	  }

	  if (input === '') {
	    return { isMatch: false, output: '' };
	  }

	  const opts = options || {};
	  const format = opts.format || (posix ? utils.toPosixSlashes : null);
	  let match = input === glob;
	  let output = (match && format) ? format(input) : input;

	  if (match === false) {
	    output = format ? format(input) : input;
	    match = output === glob;
	  }

	  if (match === false || opts.capture === true) {
	    if (opts.matchBase === true || opts.basename === true) {
	      match = picomatch.matchBase(input, regex, options, posix);
	    } else {
	      match = regex.exec(output);
	    }
	  }

	  return { isMatch: Boolean(match), match, output };
	};

	/**
	 * Match the basename of a filepath.
	 *
	 * ```js
	 * const picomatch = require('picomatch');
	 * // picomatch.matchBase(input, glob[, options]);
	 * console.log(picomatch.matchBase('foo/bar.js', '*.js'); // true
	 * ```
	 * @param {String} `input` String to test.
	 * @param {RegExp|String} `glob` Glob pattern or regex created by [.makeRe](#makeRe).
	 * @return {Boolean}
	 * @api public
	 */

	picomatch.matchBase = (input, glob, options, posix = utils.isWindows(options)) => {
	  const regex = glob instanceof RegExp ? glob : picomatch.makeRe(glob, options);
	  return regex.test(path.basename(input));
	};

	/**
	 * Returns true if **any** of the given glob `patterns` match the specified `string`.
	 *
	 * ```js
	 * const picomatch = require('picomatch');
	 * // picomatch.isMatch(string, patterns[, options]);
	 *
	 * console.log(picomatch.isMatch('a.a', ['b.*', '*.a'])); //=> true
	 * console.log(picomatch.isMatch('a.a', 'b.*')); //=> false
	 * ```
	 * @param {String|Array} str The string to test.
	 * @param {String|Array} patterns One or more glob patterns to use for matching.
	 * @param {Object} [options] See available [options](#options).
	 * @return {Boolean} Returns true if any patterns match `str`
	 * @api public
	 */

	picomatch.isMatch = (str, patterns, options) => picomatch(patterns, options)(str);

	/**
	 * Parse a glob pattern to create the source string for a regular
	 * expression.
	 *
	 * ```js
	 * const picomatch = require('picomatch');
	 * const result = picomatch.parse(pattern[, options]);
	 * ```
	 * @param {String} `pattern`
	 * @param {Object} `options`
	 * @return {Object} Returns an object with useful properties and output to be used as a regex source string.
	 * @api public
	 */

	picomatch.parse = (pattern, options) => {
	  if (Array.isArray(pattern)) return pattern.map(p => picomatch.parse(p, options));
	  return parse(pattern, { ...options, fastpaths: false });
	};

	/**
	 * Scan a glob pattern to separate the pattern into segments.
	 *
	 * ```js
	 * const picomatch = require('picomatch');
	 * // picomatch.scan(input[, options]);
	 *
	 * const result = picomatch.scan('!./foo/*.js');
	 * console.log(result);
	 * { prefix: '!./',
	 *   input: '!./foo/*.js',
	 *   start: 3,
	 *   base: 'foo',
	 *   glob: '*.js',
	 *   isBrace: false,
	 *   isBracket: false,
	 *   isGlob: true,
	 *   isExtglob: false,
	 *   isGlobstar: false,
	 *   negated: true }
	 * ```
	 * @param {String} `input` Glob pattern to scan.
	 * @param {Object} `options`
	 * @return {Object} Returns an object with
	 * @api public
	 */

	picomatch.scan = (input, options) => scan(input, options);

	/**
	 * Compile a regular expression from the `state` object returned by the
	 * [parse()](#parse) method.
	 *
	 * @param {Object} `state`
	 * @param {Object} `options`
	 * @param {Boolean} `returnOutput` Intended for implementors, this argument allows you to return the raw output from the parser.
	 * @param {Boolean} `returnState` Adds the state to a `state` property on the returned regex. Useful for implementors and debugging.
	 * @return {RegExp}
	 * @api public
	 */

	picomatch.compileRe = (state, options, returnOutput = false, returnState = false) => {
	  if (returnOutput === true) {
	    return state.output;
	  }

	  const opts = options || {};
	  const prepend = opts.contains ? '' : '^';
	  const append = opts.contains ? '' : '$';

	  let source = `${prepend}(?:${state.output})${append}`;
	  if (state && state.negated === true) {
	    source = `^(?!${source}).*$`;
	  }

	  const regex = picomatch.toRegex(source, options);
	  if (returnState === true) {
	    regex.state = state;
	  }

	  return regex;
	};

	/**
	 * Create a regular expression from a parsed glob pattern.
	 *
	 * ```js
	 * const picomatch = require('picomatch');
	 * const state = picomatch.parse('*.js');
	 * // picomatch.compileRe(state[, options]);
	 *
	 * console.log(picomatch.compileRe(state));
	 * //=> /^(?:(?!\.)(?=.)[^/]*?\.js)$/
	 * ```
	 * @param {String} `state` The object returned from the `.parse` method.
	 * @param {Object} `options`
	 * @param {Boolean} `returnOutput` Implementors may use this argument to return the compiled output, instead of a regular expression. This is not exposed on the options to prevent end-users from mutating the result.
	 * @param {Boolean} `returnState` Implementors may use this argument to return the state from the parsed glob with the returned regular expression.
	 * @return {RegExp} Returns a regex created from the given pattern.
	 * @api public
	 */

	picomatch.makeRe = (input, options = {}, returnOutput = false, returnState = false) => {
	  if (!input || typeof input !== 'string') {
	    throw new TypeError('Expected a non-empty string');
	  }

	  let parsed = { negated: false, fastpaths: true };

	  if (options.fastpaths !== false && (input[0] === '.' || input[0] === '*')) {
	    parsed.output = parse.fastpaths(input, options);
	  }

	  if (!parsed.output) {
	    parsed = parse(input, options);
	  }

	  return picomatch.compileRe(parsed, options, returnOutput, returnState);
	};

	/**
	 * Create a regular expression from the given regex source string.
	 *
	 * ```js
	 * const picomatch = require('picomatch');
	 * // picomatch.toRegex(source[, options]);
	 *
	 * const { output } = picomatch.parse('*.js');
	 * console.log(picomatch.toRegex(output));
	 * //=> /^(?:(?!\.)(?=.)[^/]*?\.js)$/
	 * ```
	 * @param {String} `source` Regular expression source string.
	 * @param {Object} `options`
	 * @return {RegExp}
	 * @api public
	 */

	picomatch.toRegex = (source, options) => {
	  try {
	    const opts = options || {};
	    return new RegExp(source, opts.flags || (opts.nocase ? 'i' : ''));
	  } catch (err) {
	    if (options && options.debug === true) throw err;
	    return /$^/;
	  }
	};

	/**
	 * Picomatch constants.
	 * @return {Object}
	 */

	picomatch.constants = constants;

	/**
	 * Expose "picomatch"
	 */

	picomatch_1 = picomatch;
	return picomatch_1;
}

var picomatch;
var hasRequiredPicomatch;

function requirePicomatch () {
	if (hasRequiredPicomatch) return picomatch;
	hasRequiredPicomatch = 1;

	picomatch = /*@__PURE__*/ requirePicomatch$1();
	return picomatch;
}

var micromatch_1;
var hasRequiredMicromatch;

function requireMicromatch () {
	if (hasRequiredMicromatch) return micromatch_1;
	hasRequiredMicromatch = 1;

	const util = require$$0$2;
	const braces = /*@__PURE__*/ requireBraces();
	const picomatch = /*@__PURE__*/ requirePicomatch();
	const utils = /*@__PURE__*/ requireUtils();

	const isEmptyString = v => v === '' || v === './';
	const hasBraces = v => {
	  const index = v.indexOf('{');
	  return index > -1 && v.indexOf('}', index) > -1;
	};

	/**
	 * Returns an array of strings that match one or more glob patterns.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * // mm(list, patterns[, options]);
	 *
	 * console.log(mm(['a.js', 'a.txt'], ['*.js']));
	 * //=> [ 'a.js' ]
	 * ```
	 * @param {String|Array<string>} `list` List of strings to match.
	 * @param {String|Array<string>} `patterns` One or more glob patterns to use for matching.
	 * @param {Object} `options` See available [options](#options)
	 * @return {Array} Returns an array of matches
	 * @summary false
	 * @api public
	 */

	const micromatch = (list, patterns, options) => {
	  patterns = [].concat(patterns);
	  list = [].concat(list);

	  let omit = new Set();
	  let keep = new Set();
	  let items = new Set();
	  let negatives = 0;

	  let onResult = state => {
	    items.add(state.output);
	    if (options && options.onResult) {
	      options.onResult(state);
	    }
	  };

	  for (let i = 0; i < patterns.length; i++) {
	    let isMatch = picomatch(String(patterns[i]), { ...options, onResult }, true);
	    let negated = isMatch.state.negated || isMatch.state.negatedExtglob;
	    if (negated) negatives++;

	    for (let item of list) {
	      let matched = isMatch(item, true);

	      let match = negated ? !matched.isMatch : matched.isMatch;
	      if (!match) continue;

	      if (negated) {
	        omit.add(matched.output);
	      } else {
	        omit.delete(matched.output);
	        keep.add(matched.output);
	      }
	    }
	  }

	  let result = negatives === patterns.length ? [...items] : [...keep];
	  let matches = result.filter(item => !omit.has(item));

	  if (options && matches.length === 0) {
	    if (options.failglob === true) {
	      throw new Error(`No matches found for "${patterns.join(', ')}"`);
	    }

	    if (options.nonull === true || options.nullglob === true) {
	      return options.unescape ? patterns.map(p => p.replace(/\\/g, '')) : patterns;
	    }
	  }

	  return matches;
	};

	/**
	 * Backwards compatibility
	 */

	micromatch.match = micromatch;

	/**
	 * Returns a matcher function from the given glob `pattern` and `options`.
	 * The returned function takes a string to match as its only argument and returns
	 * true if the string is a match.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * // mm.matcher(pattern[, options]);
	 *
	 * const isMatch = mm.matcher('*.!(*a)');
	 * console.log(isMatch('a.a')); //=> false
	 * console.log(isMatch('a.b')); //=> true
	 * ```
	 * @param {String} `pattern` Glob pattern
	 * @param {Object} `options`
	 * @return {Function} Returns a matcher function.
	 * @api public
	 */

	micromatch.matcher = (pattern, options) => picomatch(pattern, options);

	/**
	 * Returns true if **any** of the given glob `patterns` match the specified `string`.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * // mm.isMatch(string, patterns[, options]);
	 *
	 * console.log(mm.isMatch('a.a', ['b.*', '*.a'])); //=> true
	 * console.log(mm.isMatch('a.a', 'b.*')); //=> false
	 * ```
	 * @param {String} `str` The string to test.
	 * @param {String|Array} `patterns` One or more glob patterns to use for matching.
	 * @param {Object} `[options]` See available [options](#options).
	 * @return {Boolean} Returns true if any patterns match `str`
	 * @api public
	 */

	micromatch.isMatch = (str, patterns, options) => picomatch(patterns, options)(str);

	/**
	 * Backwards compatibility
	 */

	micromatch.any = micromatch.isMatch;

	/**
	 * Returns a list of strings that _**do not match any**_ of the given `patterns`.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * // mm.not(list, patterns[, options]);
	 *
	 * console.log(mm.not(['a.a', 'b.b', 'c.c'], '*.a'));
	 * //=> ['b.b', 'c.c']
	 * ```
	 * @param {Array} `list` Array of strings to match.
	 * @param {String|Array} `patterns` One or more glob pattern to use for matching.
	 * @param {Object} `options` See available [options](#options) for changing how matches are performed
	 * @return {Array} Returns an array of strings that **do not match** the given patterns.
	 * @api public
	 */

	micromatch.not = (list, patterns, options = {}) => {
	  patterns = [].concat(patterns).map(String);
	  let result = new Set();
	  let items = [];

	  let onResult = state => {
	    if (options.onResult) options.onResult(state);
	    items.push(state.output);
	  };

	  let matches = new Set(micromatch(list, patterns, { ...options, onResult }));

	  for (let item of items) {
	    if (!matches.has(item)) {
	      result.add(item);
	    }
	  }
	  return [...result];
	};

	/**
	 * Returns true if the given `string` contains the given pattern. Similar
	 * to [.isMatch](#isMatch) but the pattern can match any part of the string.
	 *
	 * ```js
	 * var mm = require('micromatch');
	 * // mm.contains(string, pattern[, options]);
	 *
	 * console.log(mm.contains('aa/bb/cc', '*b'));
	 * //=> true
	 * console.log(mm.contains('aa/bb/cc', '*d'));
	 * //=> false
	 * ```
	 * @param {String} `str` The string to match.
	 * @param {String|Array} `patterns` Glob pattern to use for matching.
	 * @param {Object} `options` See available [options](#options) for changing how matches are performed
	 * @return {Boolean} Returns true if any of the patterns matches any part of `str`.
	 * @api public
	 */

	micromatch.contains = (str, pattern, options) => {
	  if (typeof str !== 'string') {
	    throw new TypeError(`Expected a string: "${util.inspect(str)}"`);
	  }

	  if (Array.isArray(pattern)) {
	    return pattern.some(p => micromatch.contains(str, p, options));
	  }

	  if (typeof pattern === 'string') {
	    if (isEmptyString(str) || isEmptyString(pattern)) {
	      return false;
	    }

	    if (str.includes(pattern) || (str.startsWith('./') && str.slice(2).includes(pattern))) {
	      return true;
	    }
	  }

	  return micromatch.isMatch(str, pattern, { ...options, contains: true });
	};

	/**
	 * Filter the keys of the given object with the given `glob` pattern
	 * and `options`. Does not attempt to match nested keys. If you need this feature,
	 * use [glob-object][] instead.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * // mm.matchKeys(object, patterns[, options]);
	 *
	 * const obj = { aa: 'a', ab: 'b', ac: 'c' };
	 * console.log(mm.matchKeys(obj, '*b'));
	 * //=> { ab: 'b' }
	 * ```
	 * @param {Object} `object` The object with keys to filter.
	 * @param {String|Array} `patterns` One or more glob patterns to use for matching.
	 * @param {Object} `options` See available [options](#options) for changing how matches are performed
	 * @return {Object} Returns an object with only keys that match the given patterns.
	 * @api public
	 */

	micromatch.matchKeys = (obj, patterns, options) => {
	  if (!utils.isObject(obj)) {
	    throw new TypeError('Expected the first argument to be an object');
	  }
	  let keys = micromatch(Object.keys(obj), patterns, options);
	  let res = {};
	  for (let key of keys) res[key] = obj[key];
	  return res;
	};

	/**
	 * Returns true if some of the strings in the given `list` match any of the given glob `patterns`.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * // mm.some(list, patterns[, options]);
	 *
	 * console.log(mm.some(['foo.js', 'bar.js'], ['*.js', '!foo.js']));
	 * // true
	 * console.log(mm.some(['foo.js'], ['*.js', '!foo.js']));
	 * // false
	 * ```
	 * @param {String|Array} `list` The string or array of strings to test. Returns as soon as the first match is found.
	 * @param {String|Array} `patterns` One or more glob patterns to use for matching.
	 * @param {Object} `options` See available [options](#options) for changing how matches are performed
	 * @return {Boolean} Returns true if any `patterns` matches any of the strings in `list`
	 * @api public
	 */

	micromatch.some = (list, patterns, options) => {
	  let items = [].concat(list);

	  for (let pattern of [].concat(patterns)) {
	    let isMatch = picomatch(String(pattern), options);
	    if (items.some(item => isMatch(item))) {
	      return true;
	    }
	  }
	  return false;
	};

	/**
	 * Returns true if every string in the given `list` matches
	 * any of the given glob `patterns`.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * // mm.every(list, patterns[, options]);
	 *
	 * console.log(mm.every('foo.js', ['foo.js']));
	 * // true
	 * console.log(mm.every(['foo.js', 'bar.js'], ['*.js']));
	 * // true
	 * console.log(mm.every(['foo.js', 'bar.js'], ['*.js', '!foo.js']));
	 * // false
	 * console.log(mm.every(['foo.js'], ['*.js', '!foo.js']));
	 * // false
	 * ```
	 * @param {String|Array} `list` The string or array of strings to test.
	 * @param {String|Array} `patterns` One or more glob patterns to use for matching.
	 * @param {Object} `options` See available [options](#options) for changing how matches are performed
	 * @return {Boolean} Returns true if all `patterns` matches all of the strings in `list`
	 * @api public
	 */

	micromatch.every = (list, patterns, options) => {
	  let items = [].concat(list);

	  for (let pattern of [].concat(patterns)) {
	    let isMatch = picomatch(String(pattern), options);
	    if (!items.every(item => isMatch(item))) {
	      return false;
	    }
	  }
	  return true;
	};

	/**
	 * Returns true if **all** of the given `patterns` match
	 * the specified string.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * // mm.all(string, patterns[, options]);
	 *
	 * console.log(mm.all('foo.js', ['foo.js']));
	 * // true
	 *
	 * console.log(mm.all('foo.js', ['*.js', '!foo.js']));
	 * // false
	 *
	 * console.log(mm.all('foo.js', ['*.js', 'foo.js']));
	 * // true
	 *
	 * console.log(mm.all('foo.js', ['*.js', 'f*', '*o*', '*o.js']));
	 * // true
	 * ```
	 * @param {String|Array} `str` The string to test.
	 * @param {String|Array} `patterns` One or more glob patterns to use for matching.
	 * @param {Object} `options` See available [options](#options) for changing how matches are performed
	 * @return {Boolean} Returns true if any patterns match `str`
	 * @api public
	 */

	micromatch.all = (str, patterns, options) => {
	  if (typeof str !== 'string') {
	    throw new TypeError(`Expected a string: "${util.inspect(str)}"`);
	  }

	  return [].concat(patterns).every(p => picomatch(p, options)(str));
	};

	/**
	 * Returns an array of matches captured by `pattern` in `string, or `null` if the pattern did not match.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * // mm.capture(pattern, string[, options]);
	 *
	 * console.log(mm.capture('test/*.js', 'test/foo.js'));
	 * //=> ['foo']
	 * console.log(mm.capture('test/*.js', 'foo/bar.css'));
	 * //=> null
	 * ```
	 * @param {String} `glob` Glob pattern to use for matching.
	 * @param {String} `input` String to match
	 * @param {Object} `options` See available [options](#options) for changing how matches are performed
	 * @return {Array|null} Returns an array of captures if the input matches the glob pattern, otherwise `null`.
	 * @api public
	 */

	micromatch.capture = (glob, input, options) => {
	  let posix = utils.isWindows(options);
	  let regex = picomatch.makeRe(String(glob), { ...options, capture: true });
	  let match = regex.exec(posix ? utils.toPosixSlashes(input) : input);

	  if (match) {
	    return match.slice(1).map(v => v === void 0 ? '' : v);
	  }
	};

	/**
	 * Create a regular expression from the given glob `pattern`.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * // mm.makeRe(pattern[, options]);
	 *
	 * console.log(mm.makeRe('*.js'));
	 * //=> /^(?:(\.[\\\/])?(?!\.)(?=.)[^\/]*?\.js)$/
	 * ```
	 * @param {String} `pattern` A glob pattern to convert to regex.
	 * @param {Object} `options`
	 * @return {RegExp} Returns a regex created from the given pattern.
	 * @api public
	 */

	micromatch.makeRe = (...args) => picomatch.makeRe(...args);

	/**
	 * Scan a glob pattern to separate the pattern into segments. Used
	 * by the [split](#split) method.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * const state = mm.scan(pattern[, options]);
	 * ```
	 * @param {String} `pattern`
	 * @param {Object} `options`
	 * @return {Object} Returns an object with
	 * @api public
	 */

	micromatch.scan = (...args) => picomatch.scan(...args);

	/**
	 * Parse a glob pattern to create the source string for a regular
	 * expression.
	 *
	 * ```js
	 * const mm = require('micromatch');
	 * const state = mm.parse(pattern[, options]);
	 * ```
	 * @param {String} `glob`
	 * @param {Object} `options`
	 * @return {Object} Returns an object with useful properties and output to be used as regex source string.
	 * @api public
	 */

	micromatch.parse = (patterns, options) => {
	  let res = [];
	  for (let pattern of [].concat(patterns || [])) {
	    for (let str of braces(String(pattern), options)) {
	      res.push(picomatch.parse(str, options));
	    }
	  }
	  return res;
	};

	/**
	 * Process the given brace `pattern`.
	 *
	 * ```js
	 * const { braces } = require('micromatch');
	 * console.log(braces('foo/{a,b,c}/bar'));
	 * //=> [ 'foo/(a|b|c)/bar' ]
	 *
	 * console.log(braces('foo/{a,b,c}/bar', { expand: true }));
	 * //=> [ 'foo/a/bar', 'foo/b/bar', 'foo/c/bar' ]
	 * ```
	 * @param {String} `pattern` String with brace pattern to process.
	 * @param {Object} `options` Any [options](#options) to change how expansion is performed. See the [braces][] library for all available options.
	 * @return {Array}
	 * @api public
	 */

	micromatch.braces = (pattern, options) => {
	  if (typeof pattern !== 'string') throw new TypeError('Expected a string');
	  if ((options && options.nobrace === true) || !hasBraces(pattern)) {
	    return [pattern];
	  }
	  return braces(pattern, options);
	};

	/**
	 * Expand braces
	 */

	micromatch.braceExpand = (pattern, options) => {
	  if (typeof pattern !== 'string') throw new TypeError('Expected a string');
	  return micromatch.braces(pattern, { ...options, expand: true });
	};

	/**
	 * Expose micromatch
	 */

	// exposed for tests
	micromatch.hasBraces = hasBraces;
	micromatch_1 = micromatch;
	return micromatch_1;
}

var micromatchExports = /*@__PURE__*/ requireMicromatch();
const micromatch = /*@__PURE__*/getDefaultExportFromCjs(micromatchExports);

let params = parseArgv(getArgsAfterDoubleDashes(process.argv));
prepareParams(params);
exports.builder = void 0;
const originalTasks = params._;
const extraVitePlugins = [];
const defaultFusionOptions = {
  chunkDir: "chunks",
  chunkNameObfuscation: true
};
let overrideFusionOptions = {};
function useFusion(fusionOptions = {}, tasks) {
  let logger;
  let resolvedConfig;
  let exitHandlersBound = false;
  let resolvedOptions = mergeOptions(defaultFusionOptions, prepareFusionOptions(fusionOptions));
  if (typeof tasks === "string" || Array.isArray(tasks) && tasks.length > 0) {
    params._ = forceArray(tasks);
  } else {
    params._ = originalTasks;
  }
  params = mergeOptions(params, resolvedOptions.cliParams);
  return [
    {
      name: "fusion",
      configResolved(config) {
        resolvedConfig = config;
        logger = config.logger;
        config.plugins.push(...extraVitePlugins);
        for (const plugin2 of config.plugins) {
          if ("buildConfig" in plugin2) {
            plugin2.buildConfig?.(exports.builder);
          }
        }
      },
      async config(config, env) {
        let root;
        if (config.root) {
          root = node_path.resolve(config.root);
        } else {
          root = params.cwd || process.cwd();
        }
        delete config.root;
        process.chdir(root);
        exports.builder = new ConfigBuilder(config, env, resolvedOptions);
        let tasks2;
        if (typeof resolvedOptions.fusionfile === "string" || !resolvedOptions.fusionfile) {
          params.config ??= resolvedOptions.fusionfile;
          const configFile = mustGetAvailableConfigFile(root, params);
          tasks2 = await loadConfigFile(configFile);
        } else if (typeof resolvedOptions.fusionfile === "function") {
          tasks2 = expandModules(await resolvedOptions.fusionfile());
        } else {
          tasks2 = expandModules(resolvedOptions.fusionfile);
        }
        exports.builder.fusionOptions = mergeOptions(exports.builder.fusionOptions, overrideFusionOptions);
        if (params.list) {
          await displayAvailableTasks(tasks2);
          return;
        }
        const selectedTasks = selectRunningTasks([...params._], tasks2);
        const runningTasks = await resolveAllTasksAsProcessors(selectedTasks);
        for (const taskName in runningTasks) {
          const processors = runningTasks[taskName];
          for (const processor of processors) {
            await processor.config(taskName, exports.builder);
          }
        }
        exports.builder.merge(ConfigBuilder.globalOverrideConfig);
        exports.builder.merge(exports.builder.overrideConfig);
        return exports.builder.config;
      },
      outputOptions(options) {
        if (resolvedConfig.build.emptyOutDir) {
          const dir = resolvedConfig.build.outDir;
          const uploadDir = node_path.resolve(dir, "upload");
          if (fs$1.existsSync(uploadDir)) {
            throw new Error(
              `The output directory: "${dir}" contains an "upload" folder, please move this folder away or set an different fusion outDir.`
            );
          }
        }
      },
      async buildStart(options) {
        if (exports.builder.cleans.length > 0 && resolvedConfig.command !== "serve") {
          await cleanFiles(exports.builder.cleans, resolvedConfig.build.outDir || process.cwd());
        }
      },
      // Server
      configureServer(server) {
        exports.builder.server = server;
        server.httpServer?.once("listening", () => {
          const scheme = server.config.server.https ? "https" : "http";
          const address = server.httpServer?.address();
          const host = address && typeof address !== "string" ? address.address : "localhost";
          const port = address && typeof address !== "string" ? address.port : 80;
          const url = `${scheme}://${host}:${port}/`;
          const serverFile = node_path.resolve(
            server.config.root,
            resolvedOptions.cliParams?.serverFile ?? "tmp/vite-server"
          );
          fs$1.writeFileSync(node_path.resolve(server.config.root, serverFile), url);
          if (!exitHandlersBound) {
            process.on("exit", () => {
              if (fs$1.existsSync(serverFile)) {
                fs$1.rmSync(serverFile);
              }
            });
            process.on("SIGINT", () => process.exit());
            process.on("SIGTERM", () => process.exit());
            process.on("SIGHUP", () => process.exit());
            exitHandlersBound = true;
          }
        });
        const fullReloadWatches = [];
        const customWatches = [];
        const checkReload = (event) => {
          return (path) => {
            for (const watchTask of customWatches) {
              if (watchTask.file === path) {
                const mods = server.moduleGraph.getModulesByFile(watchTask.moduleFile);
                if (mods) {
                  for (const mod of mods) {
                    server.moduleGraph.invalidateModule(mod);
                  }
                  const updateType = watchTask.updateType;
                  logger.info(
                    `${chalk.green(updateType)} ${chalk.dim(node_path.relative(process.cwd(), watchTask.file))}`,
                    { timestamp: true }
                  );
                  if (updateType === "full-reload") {
                    server.ws.send({ type: "full-reload", path: "*" });
                  } else {
                    server.ws.send({
                      type: "update",
                      updates: [...mods].map((m) => ({
                        type: updateType,
                        path: m.url,
                        acceptedPath: m.url,
                        timestamp: Date.now()
                      }))
                    });
                  }
                }
              }
            }
            if (micromatch.isMatch(path, fullReloadWatches)) {
              server.ws.send({ type: "full-reload", path: "*" });
              logger.info(
                `${chalk.green("full reload")} ${chalk.dim(node_path.relative(process.cwd(), path))}`,
                { timestamp: true }
              );
            }
          };
        };
        server.watcher.on("add", checkReload());
        server.watcher.on("change", checkReload());
        for (const watchTask of exports.builder.watches) {
          if (typeof watchTask === "string") {
            const file2 = node_path.resolve(watchTask).replace(/\\/g, "/");
            fullReloadWatches.push(file2);
            server.watcher.add(file2);
            continue;
          }
          const file = node_path.resolve(watchTask.file).replace(/\\/g, "/");
          customWatches.push(watchTask);
          server.watcher.add(file);
        }
      }
      // async handleHotUpdate(ctx) {
      //   if (builder.watches.includes(ctx.file)) {
      //     if (ctx.modules.length > 0) {
      //       return ctx.modules;
      //     }
      //
      //     const modules = ctx.server.moduleGraph.getModulesByFile(ctx.file);
      //
      //     if (modules) {
      //       return [...modules];
      //     }
      //
      //     // const resolved = await ctx.server.pluginContainer.resolveId(ctx.file);
      //     // if (resolved) {
      //     //   const vm = server.moduleGraph.getModuleById(resolved.id) || server.moduleGraph.getModuleById(virtualPrefixId)
      //     //   if (vm) {
      //     //     return [vm]
      //     //   }
      //     // }
      //
      //     ctx.server.ws.send({ type: 'full-reload', path: '*' })
      //
      //     return [];
      //   }
      // }
    },
    {
      name: "fusion:pre-handles",
      enforce: "pre",
      async resolveId(source, importer, options) {
        for (const resolveId of exports.builder.resolveIdCallbacks) {
          if (typeof resolveId !== "function") {
            continue;
          }
          const result = await resolveId.call(this, source, importer, options);
          if (result) {
            return result;
          }
        }
        if (source.startsWith("hidden:")) {
          return source;
        }
      },
      async load(source, options) {
        for (const load of exports.builder.loadCallbacks) {
          if (typeof load !== "function") {
            continue;
          }
          const result = await load.call(this, source, options);
          if (result) {
            return result;
          }
        }
        if (source.startsWith("hidden:")) {
          return "";
        }
      }
    },
    {
      name: "fusion:post-handles",
      generateBundle(options, bundle) {
        for (const [fileName, chunk] of Object.entries(bundle)) {
          if (chunk.type === "chunk" && chunk.facadeModuleId?.startsWith("hidden:")) {
            delete bundle[fileName];
          }
        }
      },
      async writeBundle(options, bundle) {
        const outDir2 = resolvedConfig.build.outDir || process.cwd();
        await moveFilesAndLog(exports.builder.moveTasks, outDir2, logger);
        await copyFilesAndLog(exports.builder.copyTasks, outDir2, logger);
        await linkFilesAndLog(exports.builder.linkTasks, outDir2, logger);
        for (const callback of exports.builder.postBuildCallbacks) {
          await callback(options, bundle);
        }
        for (const [name, task] of exports.builder.tasks) {
          for (const callback of task.postCallbacks) {
            await callback(options, bundle);
          }
        }
      }
    }
  ];
}
function prepareFusionOptions(options) {
  if (typeof options === "string") {
    return {
      fusionfile: options
    };
  }
  if (typeof options === "function") {
    return {
      fusionfile: options
    };
  }
  return options;
}
function configureBuilder(handler) {
  handler(exports.builder);
}
function overrideViteConfig(config) {
  if (config === null) {
    exports.builder.overrideConfig = {};
    return;
  }
  exports.builder.overrideConfig = vite.mergeConfig(exports.builder.overrideConfig, config);
}
function overrideOptions(options) {
  return overrideFusionOptions = mergeOptions(overrideFusionOptions, options);
}
function outDir(outDir2) {
  exports.builder.overrideConfig = vite.mergeConfig(exports.builder.overrideConfig, {
    build: {
      outDir: outDir2
    }
  });
}
function chunkDir(dir) {
  exports.builder.fusionOptions.chunkDir = dir;
}
function alias(src, dest) {
  exports.builder.overrideConfig = vite.mergeConfig(exports.builder.overrideConfig, {
    resolve: {
      alias: {
        [src]: dest
      }
    }
  });
}
function externals(...externals2) {
  exports.builder.overrideConfig = vite.mergeConfig(exports.builder.overrideConfig, {
    build: {
      rollupOptions: {
        external: externals2
      }
    }
  });
}
function plugin(...plugins) {
  extraVitePlugins.push(...plugins);
}
function clean(...paths) {
  exports.builder.addCleans(...paths);
  exports.builder.cleans = uniq(exports.builder.cleans);
}
function fullReloads(...paths) {
  exports.builder.watches.push(...paths);
  exports.builder.watches = uniq(exports.builder.watches);
}
const index = {
  ...fusion,
  useFusion,
  configureBuilder,
  overrideViteConfig,
  overrideOptions,
  outDir,
  chunkDir,
  alias,
  externals,
  plugin,
  clean,
  fullReloads,
  params
};

exports.alias = alias;
exports.callback = callback;
exports.callbackAfterBuild = callbackAfterBuild;
exports.chunkDir = chunkDir;
exports.clean = clean;
exports.configureBuilder = configureBuilder;
exports.copy = copy;
exports.copyGlob = copyGlob;
exports.css = css;
exports.default = index;
exports.externals = externals;
exports.fileToId = fileToId;
exports.fullReloads = fullReloads;
exports.getGlobBaseFromPattern = getGlobBaseFromPattern;
exports.isDev = isDev;
exports.isProd = isProd;
exports.isWindows = isWindows;
exports.js = js;
exports.link = link;
exports.move = move;
exports.moveGlob = moveGlob;
exports.outDir = outDir;
exports.overrideOptions = overrideOptions;
exports.overrideViteConfig = overrideViteConfig;
exports.plugin = plugin;
exports.shortHash = shortHash;
exports.symlink = symlink;
exports.useFusion = useFusion;
//# sourceMappingURL=index.cjs.map
