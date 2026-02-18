# Program Model v2 - Multi-Course Import Plan

Last updated: 2026-02-17

## Decision Summary

Use one ZIP package per course (for example `foundation`, `vt`, `mra`) and link those courses to one logical program using a shared `program_idnumber`.

This replaces the earlier model where Foundation + Streams lived inside one course.

## Is This Easy or Difficult?

### Short answer
Medium effort, low platform risk.

### Why
- Moodle natively supports separate courses, sections (weeks), and activity bundles.
- The importer already has most required plumbing (upload, parse, validate, upsert, execute).
- Main work is schema and execution expansion from `single-course package` to `program bundle + per-course imports`.

### Effort estimate
- MVP (recommended): 4-6 dev days
- Full polish (extra validation/reporting UX): +3-5 dev days

## Target Operating Model

1. Program Owner uploads one course ZIP at a time.
2. Importer creates/updates that specific course.
3. Course carries `program_idnumber` metadata.
4. Dashboards group all courses by `program_idnumber`.
5. Enrollment decisions (Foundation -> VT/MRA) remain manual/coordinator-driven outside importer.

## Proposed Folder Contract (Per Course ZIP)

```text
course-package/
  manifest.yaml
  01. Week 1/
    01. Day 1/
      lesson_plan/
      content/
      roleplay/
      quiz/
      assignment/
      rubric/
    02. Day 2/
  02. Week 2/
  shared/
    media/
```

Parser rule:
- Numbered folder (`NN. Name` or `NN-Name`) at level 1 => `section`.
- Numbered folder at level 2 => optional `topic`.
- Known buckets (`content`, `lesson_plan`, `roleplay`, `quiz`, `assignment`, `rubric`) are always treated as activity containers.
- If no topic level exists, activities attach directly to section.

## Manifest v2 (Per Course)

Required top-level keys:
- `manifest_version`
- `program_idnumber` (shared across Foundation/VT/MRA)
- `program_name`
- `course.idnumber`
- `course.shortname`
- `course.fullname`
- `import.mode` (`assert|upsert|replace`)
- `import.dry_run`

Required item metadata:
- stable `idnumber` for all importable objects
- `section_idnumber`
- optional `topic_idnumber`
- `audience` (`learner|trainer|both`)
- `type` (`resource|lesson_plan|roleplay_assessment|quiz|assignment`)

Competencies:
- Supported at course and week scope in MVP
- Optional day scope in phase 2

Rubrics:
- Optional, but explicit warning if missing for `roleplay_assessment`/rubric-marked assignments

## Implementation Phases

### Phase 1 (MVP, do first)

1. Schema uplift in importer
- Add `program_idnumber` and multi-level `section/topic` manifest parsing.
- Keep one ZIP = one course import execution.

2. Execution mapping
- Section -> section creation/upsert.
- Optional topic -> heading/label marker within section.
- Activities -> create/upsert with current logic.

3. Program linkage
- Store `program_idnumber` as course custom field (or local mapping table).
- Add helper to query courses by program id.

4. Validation
- Enforce required keys and idnumbers.
- Warn on missing optional rubric links.
- Preserve dry-run preview fidelity.

5. Reporting minimum
- Add Program Owner view to list courses linked to program and import status.

### Phase 2 (after MVP)

1. Completeness checker
- Per course/section/topic checklist (lesson plan/content/quiz/rubric/assignment presence).

2. Progression helpers
- Optional advisory flags for “eligible for VT/MRA” based on Foundation outcomes.
- Keep enrollment action manual.

3. Better authoring aids
- CSV-to-quiz richer templates, rubric templates, manifest assistant UX.

## Tradeoffs

### What we gain
- Cleaner separation of Foundation and streams.
- Safer updates (re-import VT without touching Foundation).
- Cleaner role and enrollment operations.

### What we give up
- No single-course “all streams in one place” view.
- Cross-course learner journey needs dashboard aggregation (not automatic from Moodle core).

### When this becomes wrong
- If institution requires strict automated progression/enrollment flows immediately, we would need workflow orchestration beyond current MVP scope.

## Risks and Mitigations

1. Duplicate content from weak IDs
- Mitigation: strict idnumber validation + `upsert` matching only by idnumber.

2. Non-technical authoring errors
- Mitigation: keep guided draft manifest + explicit validation messages + dry-run required by default.

3. Mis-scoped visibility
- Mitigation: enforce `audience` at import time (trainer-only assets hidden from learners).

## Rollout Plan

1. Implement Phase 1 on existing importer branch.
2. Test with three sample packages:
- Foundation
- VT
- MRA
3. Verify Program Owner can view grouped program courses.
4. Verify Trainer/Learner visibility boundaries.
5. Freeze manifest v2 and publish authoring guide.

## Acceptance Criteria (MVP)

1. Program Owner imports Foundation ZIP, VT ZIP, and MRA ZIP independently.
2. All three courses share one `program_idnumber`.
3. Each course correctly renders sections/topics and topic-level (or section-level) activities.
4. Trainer-only artifacts are not visible to learners.
5. Re-import with `upsert` updates existing items instead of duplicates.
6. Validation blocks missing required fields and warns on optional rubric gaps.
