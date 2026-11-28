# Temporary frontend pages

## Applicability

Use when frontend testing is needed and **there is no existing page that renders the target under test**.

- The target under test may not be a page (for example, a PHP class, template, script, or package asset) and may need to be hosted on a page in order to run.
- It may be a specific component, Field, JS utility, template, Widget, and so on.
- If the target is an integration component that can be rendered independently, such as a Field or component, do not hand-write HTML to simulate it; load the component directly for testing where possible.

## Before testing

- Read the specified files to confirm what they do (including inherited or exported content).
- If PHP and JS work together (PHP registers or outputs content that JS depends on, or JS expects PHP to configure something first):
  - Read the Windwalker documentation and semantically search for `php js script pipeline`; multiple documents may need to be read together.
  - Check the instructions to determine the applicable workflow, build steps, or initialization scripts.
  - Check `e2e-testing.md` / `e2e-windwalker-auth.md` to determine whether a frontend test environment is needed.
- Use `grep` to find usages (class names, tag names, or imports).

## Choose an approach

- If an existing page renders the target: test on that page.
- If not: use a temporary page.

## Temporary pages

- Prefer to let the target configure itself (for example, register initialization scripts during PHP rendering) so it is used normally.
- Only if the target does not configure itself, find the required initialization scripts, asset registration, or helper functions in the real code. Hand-written markup may skip built-in setup and make the test invalid.
- Before evaluating behavior, confirm the target is active (the element has been upgraded or the script has run).
- To understand how setup works, how to check it, and how to rebuild, consult the Windwalker documentation or instructions to confirm the correct approach.

## Steps

1. Generate a view and route:

   ```bash
   php windwalker g view <Stage>/<ViewName>
   php windwalker g route <stage>/<view>
   ```

2. Fill in the view class name in the generated route file.
3. Add test HTML and JS to the generated empty file (which may be Blade or TS), then open the route in a browser (`e2e-testing.md`).
4. After testing, delete the generated view, route entry, and files, along with any remaining temporary code. Do not leave temporary pages in the repository.

## Related documentation

- [View asset management](https://windwalker.io/documentation/framework/core-concepts/views/asset-management.md)
- [Package JavaScript](https://windwalker.io/documentation/framework/packages/package-javascript.md)
- [Frontend overview](https://windwalker.io/documentation/framework/frontend/overview.md)
- [Code Generator](https://windwalker.io/documentation/framework/console/commands/code-generator.md)
- [MVC View](https://windwalker.io/documentation/guides/mvc/view.md)
