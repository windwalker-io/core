---
---

Inspect the specified scope in this local `windwalker/core` checkout for meaningful missing tests, then add tests to
cover the gaps.

1. Identify the requested scope from the user's request, selected files, or tagged scope. Keep investigation and
   changes within this checkout unless a directly related shared test helper must be used.
2. Read the source and neighboring tests, `.github/instructions/test.instructions.md`,
   `.github/instructions/test-common.instructions.md`, and `.github/docs/testing-architecture.md`. Follow the local
   PHPUnit conventions, use the existing test traits and fixtures, and use `.php-cs-fixer.dist.php` for style.
3. Run the current test suite and capture a source-scoped coverage baseline using an available coverage driver.
   Inspect uncovered source lines and branches, then prioritize meaningful behaviors, edge cases, and error paths. Do
   not add tests solely for logic-free getters/setters or chase coverage percentages with assertion-free tests.
4. Add or update focused tests for the verified gaps. Avoid changing production code unless a test demonstrates a
   directly related defect; keep any such fix minimal and covered by a regression test.
5. Rerun the same test and coverage scope. Also run the relevant style check and inspect the final diff.
   Preserve unrelated working-tree changes.
6. For logic-free getters/setters, follow `.github/instructions/test.instructions.md`. If one is later modified to
   include meaningful behavior, remove the `@codeCoverageIgnore` tag and cover the new behavior with tests.
7. If a source class has no tests, add a focused test class when it contains testable behavior.

Large-scope handling:

- If the scope is large (many files or subdirectories across `src/` and `tests/`), do not process it all in one pass.
  Split it into multiple iterations, one subdirectory (or small batch of files) per iteration, and
  finish each one (inspect, change, verify) before starting the next.
- Keep a short checklist of the subdirectories/files and mark each as done, so most files are genuinely inspected
  rather than skimmed, and context stays small enough to keep focus.
- Read only the files for the current iteration; avoid carrying earlier iterations' file contents forward.
- Run the targeted check/tests per iteration, then do the final overall verification and summary at the end.

Success means the requested local test scope passes, new tests assert meaningful behavior for identified gaps, and
coverage results are reported for the changed source. If a coverage driver or required external service is
unavailable, state the limitation and do not claim the uncovered scope was verified. Summarize changed files, test
results, and coverage findings.
