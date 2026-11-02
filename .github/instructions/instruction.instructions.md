# instruction.instructions.md

## Purpose

When a user asks to add certain rules into `instruction`, `skills`, or `docs`, this does not mean everything should be stuffed into `instructions`. The right approach is to split content by its nature, scope, and reusability.

This file defines the routing rule so `copilot-instructions.md` stays concise and actionable instead of turning into a giant catch-all document.

## Classification rules

### 1. `instructions`: mandatory, global, and high-priority rules

Put content here when it is:

- project-wide baseline rules and principles
- required workflows or hard constraints
- language requirements (for example, default bilingual Chinese + English writing)
- file naming conventions and repository structure rules
- security, confidentiality, and forbidden content policies
- repo-level standards that every contributor or AI agent must follow

These items should be short, clear, and mandatory.

### 2. `skills`: reusable abilities and task patterns

Put content here when it is:

- a repeatable task process
- a reusable method or operating pattern
- a skill definition with purpose, use case, expected behavior, and validation
- a workflow that can be reused across multiple tasks or repositories

If the content is "how to do a thing" and can be reused, it belongs in `SKILL.md`, not in `instructions`.

### 3. `docs`: background, explanations, and long-form references

Put content here when it is:

- long-form context or historical background
- system architecture or design explanations
- onboarding notes and process documentation
- examples, case studies, reference material, and operational explanations

If the content is "why this exists" or "long-form reference material", it belongs in `docs`, not in `instructions`.

### 4. `prompts`: reusable task templates

Put content here when it is:

- a reusable prompt template
- a task launcher or execution scaffold
- a standard AI prompt for common repository or workflow operations

This is not rule content and not a skill definition; it is a reusable execution template.

## Decision rule

When the user says "add this rule to instruction / skills / docs", use the following logic:

- if it is a mandatory repo-wide rule → `instructions`
- if it is a reusable operating pattern or capability → `skills`
- if it is background, explanation, or long-form reference → `docs`
- if it is a reusable task template → `prompts`

## Principles

- Do not put everything into `instructions`
- Keep `instructions` short, mandatory, and easy to scan
- Put long-form explanations in `docs`
- Extract reusable workflows into `skills`
- Only promote content to a skill when it is genuinely reusable and modular

## Examples

- "All content must be Markdown" → `instructions`
- "How to create a repo-init skill" → `skills`
- "Company AI collaboration background and onboarding context" → `docs`
- "A reusable prompt for repo initialization" → `prompts`

This keeps `instructions` focused, `skills` reusable, and `docs` maintainable. 
