# Instruction guidelines

This directory stores the "instruction layer" of the shared toolkit. It contains repository-level rules, constraints, routing logic, and required operational guidance.

## Purpose

- keep the essential instruction rules in one place
- maintain a concise and readable `copilot-instructions.md`
- clearly separate rules, skills, long-form documents, and prompts
- leave a scalable entry point for future instruction additions

## Contents

- [`instruction.instructions.md`](instruction.instructions.md): explains how to route content between `instructions`, `skills`, `docs`, and `prompts`
- [`architecture.instructions.md`](architecture.instructions.md): placement,
  container-layer, and extension-point rules for Core structure
- [`test.instructions.md`](test.instructions.md): test placement,
  dependency-injection, database, and controller conventions specific to Core
- [`test-common.instructions.md`](test-common.instructions.md):
  portable PHPUnit code style, test quality, fixtures, and coverage rules
- additional repo-level guidance files can be added here as new topics emerge

## Principles

- `instructions/` is not a catch-all folder; it should contain only necessary rules and standards
- content should stay short, explicit, and easy to scan
- long-form background or reference material belongs in `docs/`
- reusable workflows belong in `skills/`
- reusable task templates belong in `prompts/`
