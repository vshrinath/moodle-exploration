# Course Package Import Blueprint (Folder + Manifest)

Last updated: 2026-02-14

## Purpose

Define a creator-friendly way to build Moodle courses without heavy UI authoring:
1. Upload a content package (`.zip`).
2. Auto-generate a draft manifest.
3. Edit in guided form.
4. Validate and preview.
5. Import into Moodle.

This document is the shared reference for intent, process, structure, and guardrails.

---

## Why We Are Doing This

- Reduce friction for course creators.
- Standardize program setup across roles and streams.
- Support repeatable updates (`assert` / `upsert` / `replace`) safely.
- Keep governance strong for competency mapping, rubrics, and auditability.

---

## Scope

### In Scope (MVP + Full Authoring Controls)

- Course/section creation from package.
- Resources, assignments, quizzes, trainer-only artifacts.
- Stream structure (`Common Foundation` + `STREAM - ...`).
- Competency mapping and rubric linking.
- Validation + dry-run preview + import execution.
- Role-based ownership and approval controls.

### Out of Scope (for now)

- Azure-native worker orchestration (can be added later).
- Advanced OJT workflow automation (tracked for later phase).
- Full external form adapter (Google/Microsoft) as primary path.

---

## Role Ownership

- Program Owner:
  - Program structure, competency mapping, rubrics, publish decisions.
- Trainer:
  - Delivery assets (lesson plans/roleplay), grading execution, feedback.
- Learner:
  - Consumes learner-facing content, completes assignments/quizzes.
- System Admin:
  - Users, roles, cohorts, enrollment, reporting operations.

Policy:
- Competency mapping and rubric definition are Program Owner-controlled.
- Trainer can apply rubrics, not redefine them by default.

---

## End-to-End Process

1. Creator uploads package (`.zip`).
2. System extracts and scaffolds draft manifest from folder structure.
3. Creator edits missing metadata via guided form.
4. Validator checks structure, references, permissions, and policy.
5. Preview page shows exact actions (create/update/replace targets).
6. Import runs in selected mode.
7. Audit trail is recorded with import job ID.

---

## Import Modes

- `assert`
  - Fail on conflicts or existing IDs.
  - Use for first-time, strict publishing.
- `upsert`
  - Create missing, update existing by `idnumber`.
  - Use for iterative updates.
- `replace`
  - Replace scoped content (course/section subset).
  - Requires `dry_run` preview and explicit confirmation.

---

## ID Strategy (Required)

All importable objects require stable `idnumber`.

Recommended prefixes:
- `SEC-*` section
- `ACT-*` generic activity
- `QUIZ-*` quiz
- `ASSIGN-*` assignment
- `COMP-*` competency
- `RUB-*` rubric
- `ROLEPLAY-*` roleplay
- `LP-*` lesson plan

Matching rule: imports match by `idnumber`, not by title.

---

## Versioning Strategy (Required)

Manifest fields:
- `manifest_version` (schema version)
- `program_version` (curriculum version)
- `package_version` (content build version)
- `change_note` (required for replace-mode)

---

## Package Structure (Recommended)

```text
program-package/
  manifest.yml
  assets/
    common/
      ...
    streams/
      frontdesk/
      doctor-assistance/
      medical-records/
  quizzes/
    frontdesk_quiz.xml
    common_foundation.gift
  lesson_plans/
    trainer_session_plan_week1.pdf
  roleplay/
    frontdesk_patient_intake_scenario.pdf
  rubrics/
    case_viva_rubric.json
```

---

## Manifest Design (v1)

```yaml
manifest_version: "1.0"
program_version: "2026.1"
package_version: "2026.1.3"
change_note: "Stream content refresh + rubric update"

course:
  idnumber: "COURSE-AAP-2026"
  shortname: "AAP-2026"
  fullname: "Allied Assist Program 2026"
  category_idnumber: "allied-assist"

import:
  mode: "upsert"          # assert | upsert | replace
  dry_run: true
  scope: "course"         # course | section

sections:
  - idnumber: "SEC-COMMON"
    name: "Common Foundation"
    order: 1
  - idnumber: "SEC-STREAM-FRONTDESK"
    name: "STREAM - Front Desk Management"
    order: 2

competencies:
  - idnumber: "COMP-PROF-ETHICS"
    name: "Professional Ethics"
    mandatory: true
    prerequisite_gate: true
    progression_level: "supervised"   # supervised | indirect | independent

rubrics:
  - idnumber: "RUB-CASE-VIVA-01"
    name: "Case Viva Rubric"
    owner_role: "program_owner"

activities:
  - idnumber: "ACT-ETHICS-RESOURCE"
    type: "resource"
    section_idnumber: "SEC-COMMON"
    title: "Professional Ethics Intro"
    file: "assets/common/ethics_intro.pdf"
    audience: "learner"
    competencies: ["COMP-PROF-ETHICS"]

  - idnumber: "ASSIGN-CASE-01"
    type: "assignment"
    section_idnumber: "SEC-STREAM-FRONTDESK"
    title: "Case Submission"
    submission_types: ["file", "online_text"]
    allowed_filetypes: ["pdf", "docx", "mp4", "mov", "mp3", "wav"]
    group_submission: true
    rubric_idnumber: "RUB-CASE-VIVA-01"
    reviewer_role: "trainer"
    moderation_required: false
    competencies: ["COMP-PROF-ETHICS"]

  - idnumber: "QUIZ-FRONTDESK-01"
    type: "quiz"
    section_idnumber: "SEC-STREAM-FRONTDESK"
    title: "Front Desk Quiz"
    quiz_source:
      format: "moodle_xml" # moodle_xml | gift | inline
      path: "quizzes/frontdesk_quiz.xml"

  - idnumber: "LP-WEEK1"
    type: "lesson_plan"
    section_idnumber: "SEC-COMMON"
    title: "Week 1 Trainer Plan"
    file: "lesson_plans/trainer_session_plan_week1.pdf"
    audience: "trainer"

  - idnumber: "ROLEPLAY-INTAKE-01"
    type: "roleplay_assessment"
    section_idnumber: "SEC-STREAM-FRONTDESK"
    title: "Patient Intake Roleplay"
    file: "roleplay/frontdesk_patient_intake_scenario.pdf"
    audience: "trainer"
    rubric_idnumber: "RUB-CASE-VIVA-01"
    competencies: ["COMP-PROF-ETHICS"]

stream_choice:
  title: "Choose Your Stream"
  section_idnumber: "SEC-COMMON"
  options:
    - "Front Desk Management"
    - "Doctor Assistance"
    - "Medical Records"

policy:
  pii_classification: "moderate"    # none | low | moderate | high
  consent_required: true
  consent_text_ref: "CONSENT-GROUP-MEDIA-2026"
  retention_days: 365
```

---

## Quiz Format Guidance

Supported formats:
- Preferred: `moodle_xml` (best fidelity).
- Also supported: `gift` (fast authoring).
- Optional: inline manifest questions (small/simple quizzes only).

### Non-Technical Quiz Authoring (Recommended)

Creators should not need to write Moodle XML directly.

Use a human-friendly intake format:
1. Spreadsheet template (`.xlsx` / Google Sheet), or
2. Guided web form in Moodle importer UI.

Then convert automatically to internal quiz payload (`moodle_xml`/manifest structure) during validation/import.

Suggested spreadsheet columns:
- `question_id`
- `question_type` (`mcq`, `true_false`, `short_answer`, `case_viva`)
- `question_text`
- `option_a`, `option_b`, `option_c`, `option_d`, `option_e`
- `correct_option` (or expected short answer)
- `explanation`
- `marks`
- `competency_idnumber`
- `difficulty` (`basic`, `intermediate`, `advanced`)
- `audience` (`learner`, `trainer`)

Converter behavior:
- Normalize and validate each row.
- Fail with row-level error messages for missing required fields.
- Generate preview before import.
- Produce machine-friendly quiz package for Moodle import execution.

---

## File Type Guidance

Allowed by default:
- Documents: `pdf`, `doc`, `docx`, `ppt`, `pptx`, `txt`
- Media: `mp4`, `mov`, `mp3`, `wav`
- Quiz sources: `xml` (Moodle XML), `gift`

External references (YouTube/web):
- Store in manifest as typed external resources (`youtube`, `url`, `guideline`).

---

## Validation Rules (Minimum)

- Manifest schema and required fields are present.
- Every object has unique `idnumber`.
- Referenced files exist in package.
- Section references are valid.
- Competency and rubric references resolve or are creatable by policy.
- Role ownership checks:
  - Program Owner required for competency/rubric changes.
- Replace mode requires confirmation and preview.

---

## Content Quality Checks (Required)

Before import is allowed, run a quality gate and block on critical issues.

### Critical (block import)
- Duplicate `idnumber` values.
- Missing required files or broken manifest references.
- Quiz items with missing correct answer.
- Rubric-linked activities where rubric is missing.
- Competency references to non-existent `competency_idnumber`.
- Empty activity title/instructions for graded items.

### Warning (allow import with explicit acknowledgement)
- Duplicate or near-duplicate quiz question text.
- MCQ distractors that are identical or blank.
- External links unreachable at validation time.
- Media assets without transcript/caption metadata.
- Assignment instructions below minimum content threshold.

### Suggested scoring model
- Each package gets a quality score (`0-100`).
- Default publish threshold: `>= 80` and no critical issues.
- Quality report is shown in preview with item-level fixes.

---

## Version Rollback Behavior (Required)

Every import job should be reversible at scoped granularity.

### Rollback model
1. Store import job manifest snapshot and action ledger.
2. For `replace` imports, create pre-import snapshot for affected scope.
3. Allow rollback by import job ID:
- `full` rollback (entire job scope), or
- `selective` rollback (specific objects by `idnumber`).

### Rollback constraints
- Rollback does not delete learner submissions/grades by default.
- If object has live learner data, rollback performs safe-disable/archive unless explicit override is approved.
- Rollback actions are fully audited with actor and timestamp.

### Operational policy
- Keep rollback points for at least the last 20 imports per course (configurable).
- Require Program Owner approval for rollback affecting competencies/rubrics.
- Require System Admin approval for rollback affecting enrollment/role-sensitive structures.

---

## Audit and Compliance

Log all import actions:
- actor, role, timestamp
- object `idnumber`
- action (`create`, `update`, `replace`, `skip`, `error`)
- source (`manifest_import`)
- import job ID
- policy metadata (PII/consent flags used)

---

## PII and Consent Policy

Manifest can mark sensitivity:
- `pii_classification`
- `consent_required`
- `retention_days`

Use this for:
- learner audio/video submissions
- patient-resembling case media
- any content requiring explicit consent policy handling

---

## Phased Implementation

### Phase 1 (MVP)
- Upload zip
- Scaffold manifest
- Guided edit
- Validate + preview
- Import sections/resources/basic assignments/quizzes
- Modes: assert + upsert

### Phase 2 (Full Authoring Controls)
- Replace mode with strict safeguards
- Competency mandatory/optional + gating flags
- Rubric authoring/linking and role controls
- Versioning enforcement
- Extended audit trail + policy checks

---

## Open Decisions to Finalize Before Build

1. Default max upload size and file-type whitelist by role.
2. Replace-mode scope limits (full course vs section-only in v1).
3. Default moderation policy for roleplay/case viva.
4. Mandatory fields for program publish readiness.
5. Whether external links require approval/review workflow.
6. Which non-technical intake channels are enabled in v1:
   - spreadsheet upload only, or
   - spreadsheet + guided in-app form.
7. Who can approve converted quiz content before publish (Program Owner only vs Program Owner + delegated reviewer).

---

## Non-Technical UX Additions (Recommended)

To keep authoring friendly for non-technical users, add:

1. Template Library
- Downloadable templates for quiz, assignment, roleplay, and lesson plan metadata.

2. Guided Form Fallback
- If no manifest is uploaded, prompt users through a wizard and generate manifest automatically.

3. Row-Level Validation Messages
- Show exact field, row, and corrective action (not generic errors).

4. Preview in Plain Language
- "Will create 3 sections, 12 activities, 2 quizzes, 1 rubric link" before import.

5. Draft Save + Resume
- Users can save incomplete package drafts and return later.

6. Import Explain Mode
- Show why an item is in `Do Now` / blocked / skipped after import.

---

## Related Documents

- `docs/USER_FAQ.md`
- `docs/SYSTEM_FAQ.md`
- `docs/USER_WORKFLOWS.md`
- `docs/PRAGMATIC_IMPLEMENTATION_GUIDE.md`
