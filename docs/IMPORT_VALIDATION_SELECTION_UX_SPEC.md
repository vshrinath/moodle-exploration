# ZIP Validation and Selection UX Spec

## Purpose
Define a production-friendly import flow where Program Owners and SysAdmins can:
- Select/create program and course with minimal inputs.
- Validate package contents before import.
- Optionally choose which detected files should be imported or replaced.

This document is a UI/behavior spec only. No implementation details are required to read it.

## Goals
- Keep importer non-intimidating for non-technical users.
- Make import actions explicit and reversible before execution.
- Reduce accidental overwrite by requiring visual confirmation.
- Preserve debug visibility for admins without exposing technical noise by default.

## Core Flow
1. Program step:
- Choose `Use existing program` or `Create new program`.
- If existing: show program dropdown only.
- If new: show `Program ID number` and `Program name` only.

2. Course step:
- Choose `Use existing course` or `Create new course`.
- If existing: show target course dropdown only.
- If new: show `Course full name` only.

3. Package step:
- Upload ZIP.
- Click `Validate ZIP file`.

4. Validation result:
- `Ready to import` (green) if no blocking errors.
- `Needs fixes` (red/amber) if blocking errors.
- Import button enabled only in `Ready` state.

5. Optional review-and-select step (next increment):
- Show folder/file tree with status tags:
  - `New`
  - `Update`
  - `Unchanged`
  - `Missing in ZIP` (replace mode only)
- Allow checkboxes for import scope selection.
- Show clear warning on discard: user must re-upload ZIP.

## Information Architecture
### Default (production) view
- Validation status.
- Concise error/warning list in plain language.
- Import action button state.

### Debug view
- Hidden under `Show debug details`.
- Full manifest YAML.
- Parsed section/topic/activity tables.

## Status Semantics
- Blocking errors:
  - Invalid structure.
  - Missing required fields.
  - Invalid references.
- Non-blocking warnings:
  - Optional metadata not present.
  - Recommended but non-required mappings.

Import action is blocked on any blocking error.

## Selection Rules (planned)
- New files: selected by default.
- Updated files: selected by default.
- Unchanged files: disabled, not selectable.
- Missing-in-ZIP (replace mode): shown separately for explicit confirmation.

## Quiz Preview (planned)
- For CSV-driven quiz entries in package:
  - Offer `Preview quiz` link before import.
  - Show question text, options, and configured correct option.
- Preview is read-only and for verification only.

## Replace Behavior (planned)
- Upsert remains default.
- Add scoped replace options:
  - Replace by section.
  - Replace by topic.
- Replace action requires explicit confirmation and impact summary.

## Out of Scope (this phase)
- Full content diff of binary files.
- Auto-merge conflict resolution across imports.
- Workflow automation after import completion.
