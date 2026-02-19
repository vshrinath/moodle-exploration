# Skills Index

A portable library of role-based AI personas for software development and content projects. Load only the skills you need for the current task.

---

## Quick Reference

### Standard workflow (full feature)
```
@pm → @ux → @arch → @dev → @guard → @qa → @ops
```

### Small coding fix
```
@dev → @guard
```

### Content / marketing
```
@writer → @seo
```

### Bug investigation
```
@debugging → @dev → @guard
```

---

## All Skills

### Coding

| Skill | File | Use when |
|-------|------|----------|
| `@arch` | `coding/arch.md` | Architectural decisions, system design, service boundaries |
| `@dev` | `coding/dev.md` | Any implementation task — backend, frontend, or both |
| `@guard` | `coding/guard.md` | Code review, security audit, convention drift check |
| `@qa` | `coding/qa.md` | Testing, edge cases, regression verification |
| `@self-review` | `coding/self-review.md` | Pre-handoff quality check before requesting code review |
| `@api-design` | `coding/api-design.md` | Designing or reviewing API endpoints and contracts |
| `@data-modeling` | `coding/data-modeling.md` | Schema design, model relationships, migrations |
| `@debugging` | `coding/debugging.md` | Bug investigation, intermittent failures, root cause analysis |
| `@performance` | `coding/performance.md` | Backend performance: slow queries, caching, API optimization |
| `@frontend-perf` | `coding/frontend-performance.md` | Frontend performance: Core Web Vitals, bundle size, image optimization |
| `@refactoring` | `coding/refactoring.md` | Code smells, safe structural cleanup, tech debt reduction |

### Design

| Skill | File | Use when |
|-------|------|----------|
| `@ux` | `design/ux.md` | User flows, component states, accessibility, form design |

### Marketing

| Skill | File | Use when |
|-------|------|----------|
| `@writer` | `marketing/writer.md` | Articles, newsletters, social posts, email campaigns |
| `@seo` | `marketing/seo.md` | Meta tags, structured data, technical SEO |
| `@perf` | `marketing/perf.md` | Ad copy, landing pages, UTM tracking, A/B tests |
| `@video` | `marketing/video.md` | **Remotion-specific** video production (React/TypeScript compositions) |

### Product

| Skill | File | Use when |
|-------|------|----------|
| `@pm` | `product/pm.md` | Feature scoping, requirements, acceptance criteria |
| `@task-decomposition` | `product/task-decomposition.md` | Breaking features into small, testable tasks with dependencies |

### Meta (Autonomous Operation)

| Skill | File | Use when |
|-------|------|----------|
| `@confidence-scoring` | `meta/confidence-scoring.md` | Assessing confidence level, determining when to ask for help |
| `@context-strategy` | `meta/context-strategy.md` | Managing limited context window, efficient file navigation |
| `@error-recovery` | `meta/error-recovery.md` | Handling test/build/deployment failures autonomously |

### Operations

| Skill | File | Use when |
|-------|------|----------|
| `@ops` | `ops/ops.md` | Deployment, environment management, incident response |
| — | `ops/cicd-pipelines.md` | GitHub Actions CI/CD setup and configuration |
| — | `ops/deploy-aws.md` | AWS infrastructure (ECS, RDS, S3, CloudFront, Route 53) |
| — | `ops/deploy-python-django.md` | Django-specific deployment (Dockerfile, settings, health checks) |
| — | `ops/deployment-practices.md` | Universal deployment principles (any stack) |

---

## Secondary Skills — When to Pair

Secondary skills are invoked *alongside* a primary skill, not instead of one.

| If you're doing... | Also invoke |
|--------------------|-------------|
| Implementing features that touch the API | `@api-design` |
| Implementing features that change models/schema | `@data-modeling` |
| Implementing backend features on hot paths or with caching | `@performance` |
| Implementing frontend features with performance concerns | `@frontend-perf` |
| Cleaning up code as part of a task | `@refactoring` |
| Completing implementation before handoff | `@self-review` |
| Reviewing API surface changes | `@api-design` |
| Reviewing schema changes or migrations | `@data-modeling` |
| Reviewing backend performance-critical code | `@performance` |
| Reviewing frontend performance-critical code | `@frontend-perf` |
| Reviewing code with quality issues needing structure changes | `@refactoring` |
| Investigating a bug | `@debugging` |
| Architectural decisions involving API design | `@api-design` |
| Architectural decisions involving data models | `@data-modeling` |
| Planning work breakdown | `@task-decomposition` |
| Assessing task difficulty | `@confidence-scoring` |
| Managing large codebase navigation | `@context-strategy` |
| Handling failures or errors | `@error-recovery` |

---

## Handoff Chain

```
@pm          →  Scoped requirements with acceptance criteria
  @ux        →  User flows and component specs
    @arch    →  System design and implementation plan
      @dev   →  Working implementation
        @self-review → Pre-handoff quality check
          @guard →  Code review: security, correctness, conventions
            @qa  →  Testing: edge cases, coverage, regressions
              @ops →  Deployment: checklist, environment, monitoring
```

**Skip any step that doesn't apply.** A bug fix doesn't need `@pm` or `@ux`. A content task doesn't need `@arch` or `@ops`.

---

## Autonomous Workflow

For fully autonomous agentic development:

```
1. @task-decomposition  →  Break feature into small, testable tasks
2. @confidence-scoring  →  Assess confidence for each task; ask before low-confidence ones
3. @context-strategy    →  Load only what's needed for the current task
4. @dev                 →  Implement
5. @self-review         →  Check own work before handoff
6. @guard               →  Security/convention review
7. @qa                  →  Test
8. @ops                 →  Deploy
```

**`@error-recovery` is not a sequential step** — invoke it at any point in the above when a test, build, or deployment fails. It handles failure analysis and retry logic for steps 4–8.

**`@confidence-scoring` applies per task**, not just once at the start. Re-assess before each task in the decomposed list.

---

## Portability Notes

These skills are written to be **project-agnostic**. When dropping them into a new project:

- `@dev` and `@qa` will look for a CONVENTIONS.md, CONTRIBUTING.md, or equivalent file; if none exists, they match existing code patterns
- `@arch` reads the project's conventions file before proposing structural changes
- `@data-modeling` and `@performance` and `@frontend-perf` use pseudocode for principles and clearly labeled sections for framework-specific examples (Django, Prisma, Next.js, Astro)
- `@video` is **Remotion-specific** — it does not apply to other video workflows
- Ops skills assume Docker + GitHub Actions + AWS as the deployment stack; adapt as needed for other infrastructure
