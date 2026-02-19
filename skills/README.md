# Skills System — AI-Assisted Development

This directory contains role-based personas and strategies for autonomous AI-assisted development.

## Directory Structure

```
skills/
├── coding/          # Implementation skills
├── ops/             # Deployment and infrastructure
├── product/         # Planning and decomposition
├── meta/            # Autonomous operation strategies
├── marketing/       # Content and promotion
├── design/          # UX/UI design
└── README.md        # This file
```

---

## Coding Skills

**Core implementation and quality:**

- `@arch` — Architecture and design decisions
- `@dev` — Implementation (backend + frontend)
- `@guard` — Security, code review, convention drift
- `@qa` — Testing and quality verification

**Advanced skills:**

- `@performance` — Backend optimization (database queries, caching, API performance)
- `@frontend-perf` — Frontend optimization (Core Web Vitals, bundle size, images)
- `@debugging` — Systematic debugging methodology
- `@refactoring` — Safe refactoring patterns and code smell detection
- `@api-design` — REST API design principles
- `@data-modeling` — Database design, normalization, migrations
- `@self-review` — Pre-handoff quality checks

---

## Ops Skills

**Deployment practices (universal):**

- `deployment-practices.md` — Multi-stage builds, dependency locking, env validation

**Language-specific deployment:**

- `deploy-python-django.md` — Python/Django implementation guide
- `deploy-php-composer.md` — PHP/Composer (add when needed)
- `deploy-nodejs-npm.md` — Node.js/npm (add when needed)

**CI/CD:**

- `cicd-pipelines.md` — GitHub Actions, testing gates, automation

**Cloud-specific deployment:**

- `deploy-aws.md` — ECS, ECR, RDS, S3, CloudFront
- `deploy-azure.md` — (add when needed)
- `deploy-gcp.md` — (add when needed)

**General operations:**

- `@ops` — High-level deployment checklist, monitoring, incidents

---

## Product Skills

**Planning and decomposition:**

- `@task-decomposition` — Breaking features into small, testable tasks
  - Vertical slices over horizontal layers
  - Dependency mapping
  - Parallel vs sequential work identification
  - Agentic development time estimates

---

## Meta Skills

**Autonomous operation strategies:**

- `@confidence-scoring` — When to ask for help
  - Confidence level assessment (0-100%)
  - Escalation criteria
  - Documenting uncertainty
  - Calibration over time

- `@context-strategy` — Managing limited context window
  - Just-in-time loading
  - Surgical reads (specific functions, not entire files)
  - Summarize and release
  - Navigation strategies

- `@error-recovery` — Handling failures autonomously
  - Analyze before fixing
  - Fix root cause, not symptoms
  - Smart retry logic
  - Escalation criteria

- `@self-review` — Pre-handoff quality checks
  - Comprehensive checklist (security, performance, correctness)
  - Common mistake patterns
  - When to ask for review

---

## Marketing Skills

- `@video` — Video production (Remotion, captures, assets)
- `@writer` — Content writing
- `@seo` — SEO and discoverability
- `@perf` — Performance marketing

---

## Design Skills

- `@ux` — UX/UI design and interaction specs

---

## How to Use

### For Current Project

**Load skills as needed:**

```
Building a feature:
@pm → @ux → @arch → @dev → @guard → @qa → @ops

Small fix:
@dev → @guard

Performance issue (backend):
@performance → @dev → @qa

Performance issue (frontend):
@frontend-perf → @dev → @qa

Debugging:
@debugging → @dev → @guard
```

**Autonomous workflow:**

```
1. @task-decomposition — Break feature into tasks
2. @confidence-scoring — Assess confidence for each task
3. @context-strategy — Load only what's needed
4. @dev — Implement
5. @self-review — Check own work
6. @guard — Security/convention review
7. @qa — Test
8. @error-recovery — Handle failures
9. @ops — Deploy
```

### For New Projects

**Copy this entire skills/ directory to new project:**

```bash
cp -r skills/ /path/to/new-project/
```

**Activate relevant skills:**

1. **Choose language stack:**
   - Python/Django → Keep `deploy-python-django.md`
   - PHP/Laravel → Create `deploy-php-laravel.md`
   - Node.js → Create `deploy-nodejs-express.md`

2. **Choose cloud provider:**
   - AWS → Keep `deploy-aws.md`
   - Azure → Create `deploy-azure.md`
   - GCP → Create `deploy-gcp.md`

3. **Universal skills work everywhere:**
   - All `coding/` skills
   - All `meta/` skills
   - All `product/` skills
   - `deployment-practices.md`
   - `cicd-pipelines.md`

4. **Create project-specific:**
   - `CONVENTIONS.md` — Project patterns
   - `.env.example` — Environment variables
   - `README.md` — Setup instructions

---

## Skill Portability

### Highly Portable (Copy as-is)

✅ All coding skills (arch, dev, guard, qa, performance, frontend-perf, debugging, refactoring, api-design, data-modeling, self-review)
✅ All meta skills (confidence-scoring, context-strategy, error-recovery)
✅ All product skills (task-decomposition)
✅ Universal deployment practices
✅ CI/CD pipelines

### Needs Customization

⚠️ Language-specific deployment (Python vs PHP vs Node)
⚠️ Cloud-specific deployment (AWS vs Azure vs GCP)
⚠️ Domain-specific requirements (SaaS vs e-commerce vs content)

### Project-Specific (Don't Copy)

❌ CONVENTIONS.md (every project is different)
❌ Existing codebase context
❌ Tech stack decisions

---

## Estimated Speedup

**Current project (learning phase):**
- Setup: Weeks
- Feature velocity: Moderate
- Deployment: Manual

**Future projects (with full skills system):**
- Setup: Hours
- Feature velocity: 3-5x faster
- Deployment: Automated

**Key factors:**
- Greenfield projects: 3-5x faster
- Brownfield projects: 2-3x faster
- Solo work: Maximum benefit
- Team work: Shared conventions benefit

---

## Maintenance

### When to Update Skills

**Update when:**
- New patterns emerge (3+ occurrences)
- Better practices discovered
- Framework/tool updates
- Lessons learned from failures

**Don't update for:**
- One-off situations
- Project-specific quirks
- Temporary workarounds

### Version Control

- Commit skills/ to project repository
- Tag major skill updates
- Document changes in RELEASE_NOTES.md

---

## Further Reading

- `AGENTS.md` — Rules for AI-assisted development
- `CONVENTIONS.md` — Project-specific patterns
- Individual skill files for detailed guidance
