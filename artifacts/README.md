# Test Artifacts

This directory contains test evidence and artifacts from validation runs.

## Directory Structure

```
artifacts/
└── allied-health-e2e/          # Allied Health end-to-end test evidence
    ├── phase2-quiz-grading/    # Phase 2: Quiz and grading workflow
    ├── phase3-cohort-regression/  # Phase 3: Cohort lifecycle and regression
    └── YYYYMMDD_HHMMSS/        # Timestamped test runs
```

## Contents

### Phase 2: Quiz and Grading Workflow
- Quiz attempt screenshots
- Grading interface evidence
- Gradebook consistency checks

### Phase 3: Cohort Lifecycle and Regression
- Baseline participant lists
- Cohort removal/re-add effects
- Visibility regression checks
- Quiz history preservation
- Gradebook consistency validation

## Usage

**Viewing artifacts:**
```bash
# List all test runs
ls -la artifacts/allied-health-e2e/

# View specific phase
ls -la artifacts/allied-health-e2e/phase3-cohort-regression/
```

**Adding new artifacts:**
1. Create timestamped directory: `YYYYMMDD_HHMMSS/` or named phase directory
2. Add screenshots, logs, or other evidence
3. Reference in test report (e.g., `PHASE_3_COHORT_LIFECYCLE_TEST_REPORT.md`)

## Retention

- Keep artifacts for current and previous test cycle
- Archive older artifacts if needed for compliance
- Artifacts are tracked in git for team visibility

## Related Documentation

- `docs/PHASE_3_COHORT_LIFECYCLE_TEST_REPORT.md` — Latest test report
- `docs/WORKFLOW_SIMULATION_GOLDEN_TEST_SUITE.md` — Test suite documentation
- `docs/ALLIED_HEALTH_FOUNDATIONAL_COURSE_WORKFLOW.md` — Workflow being tested

---

**Last Updated**: 2026-02-21
