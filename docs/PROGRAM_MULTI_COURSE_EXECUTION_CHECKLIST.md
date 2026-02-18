# Program Model v2 - Execution Checklist

Last updated: 2026-02-17

## Scope

Implement the new model:
- One import package per course (`foundation`, `vt`, `mra`, ...)
- Shared `program_idnumber` links all courses into one logical program
- Enrollment progression decisions remain manual (outside importer)

## P1 - Core (Do First)

1. Manifest v2 metadata
- [x] Add `program_idnumber` and `program_name` input fields in importer UI.
- [x] Include program metadata in generated manifest preview.
- [x] Include target course metadata in manifest (`course.idnumber`, `course.shortname`, `course.fullname`).
- [x] Add preview warnings when program metadata is missing.

2. Validation hardening
- [ ] Enforce required `idnumber` at all levels (course/section/topic/activity).
- [ ] Enforce unique IDs within package scope.
- [ ] Validate section/topic references for all activities.
- [ ] Validate audience values (`learner|trainer|both`).

3. Execution mapping
- [ ] Add section/topic mapping support (optional topic -> heading/label marker).
- [ ] Ensure upsert updates existing items instead of creating duplicates.
- [ ] Keep current quiz/resource/assignment behavior stable while section/topic support is added.

4. Program linkage storage
- [ ] Decide storage: course custom field vs local mapping table.
- [ ] Implement linkage write on import execute.
- [ ] Add helper query to fetch all courses by `program_idnumber`.

## P2 - Program Owner Verification

1. Program-level visibility
- [ ] Add Program Owner page/card to list linked courses under each `program_idnumber`.
- [ ] Show import status summary per linked course.

2. Completeness checks
- [ ] Add checklist by course/section/topic:
  - lesson plan present
  - content present
  - quiz present
  - assignment present (if required)
  - rubric present (if required)

3. Warnings clarity
- [ ] Group optional warnings (rubric, competency links, trainer-only docs) as actionable checklist items.

## P3 - Workflow and Scale

1. Enrollment operations
- [ ] Add explicit workflow note and UI hint:
  - Foundation completion reviewed by coordinator
  - Coordinator/System Admin enrolls learner into VT or MRA

2. Dashboard aggregation
- [ ] Show learner/trainer cards grouped by program (via `program_idnumber`) while still linking into separate courses.

3. Authoring quality
- [ ] Publish manifest v2 authoring template for content teams.
- [ ] Add examples for topic bundles (lesson_plan/content/roleplay/quiz/assignment/rubric).

## Acceptance Checks

1. Import `foundation.zip` with Program A ID.
2. Import `vt.zip` with same Program A ID.
3. Import `mra.zip` with same Program A ID.
4. Verify all three appear under one logical program in Program Owner view.
5. Verify learner sees only learner-facing activities.
6. Verify trainer sees trainer-only and learner-facing materials.
7. Verify re-import `vt.zip` (upsert) updates existing IDs without duplicates.

## Notes

- Keep imports deterministic: identity is always `idnumber`, never title.
- Keep manual progression decisions outside importer to avoid accidental auto-enrollment.
- Do not move to automated progression until Program Owner validation workflow is stable.
