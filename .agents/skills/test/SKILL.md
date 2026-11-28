---
name: test
description: Shared Test skill for finding testing commands, documentation, and skills from a single entry point.
---

# Skill: test

## Purpose

When users ask to run or write tests, agents can use this skill to find testing-related commands, documentation, and skills.

Because projects may use multiple technology stacks, testing commands, documentation, and skills may be spread across different paths. This skill provides a unified entry point so agents can quickly find testing resources.

## When to use

- Writing project tests
- Running tests
- Generating code coverage
- Using code coverage results to find code that lacks tests
- Performing E2E integration tests

## Scope

This skill mainly provides commonly used testing commands for different technology stacks. If an individual project has its own testing commands and workflows, refer to that project's instructions first.

## Testing knowledge and guidelines

- Shared knowledge for all testing work: `references/test-common.md`
- Testing for PHP / the Windwalker framework: `references/windwalker-testing.md`
  - PhpUnit testing guidelines: `references/phpunit-testing.md`
  - App testing (tiers, automatic reset, request scope, helper traits): `references/app-testing.md`
  - Database testing (InMemory, real DBMS): `references/database-testing.md`
  - Coverage guidelines: `references/coverages.md`
  - Smoke tests / temporary executable files: `references/smoke-test.md`
- E2E / Playwright testing guidelines: `references/e2e-testing.md`
  - Windwalker login flow (credential file, session cookie, session bridge): `references/e2e-windwalker-auth.md`
  - Temporary frontend pages (when no existing page renders the target under test): `references/frontend-scratch-page.md`

## Validation

- Review the testing syntax and APIs again to ensure they match the current state of the test suite.
- Confirm that unnecessary tests for simple getters/setters have not been added and that `@codeCoverageIgnore` has been applied.
- Confirm that getters/setters containing logic are tested or are not incorrectly marked with `@codeCoverageIgnore`.
