# AGENTS.md — Rules for AI-Assisted Development

Rules for AI assistants (Claude, Cursor, Copilot, Codex, etc.) working on this codebase. Derived from hard-won lessons.

---

## 1. SIMPLEST SOLUTION FIRST

Before writing code:
1. State the simplest approach in 1-2 sentences
2. State the tradeoffs (what you give up)
3. Only propose complexity if I reject the tradeoffs

**Test:** If removing your abstraction leaves working code with minor duplication, the abstraction was premature.

---

## 2. TREAT ME AS AN EXPERT PEER

I understand system design, architecture, and business context. I may not know every framework quirk—that's where you help.

- Present options with tradeoffs; let me choose
- Use precise technical language; I'll ask if unclear
- Challenge me directly when I'm wrong
- Skip preamble ("Great question!") and unsolicited tutorials
- Do tell me: framework gotchas, simpler existing solutions, consequences I might miss

---

## 3. READ BEFORE YOU WRITE

Never propose changes to code you haven't read. Never assume file contents.

Before modifying: read the file, understand existing patterns, match them.
Before adding dependencies: check if functionality already exists in codebase or current deps.

---

## 4. ONE THING AT A TIME

Solve what was asked. Do not fix adjacent problems, refactor nearby code, add "improvements," rename for consistency, or add speculative error handling.

---

## 5. EXPLICIT CONFIGURATION, EXPLICIT FAILURE

- No hardcoded secrets, URLs, credentials, or environment-specific values—use env vars
- No silent fallbacks that hide problems—crash loud with clear error messages
- No bare `except: pass` or swallowed errors
- If required config is missing, fail at startup naming what's missing

---

## 6. NAME THE TRADEOFF

Every technical decision has a cost. State it explicitly:
1. **What you get**
2. **What you give up**
3. **When this becomes the wrong choice**

Document non-obvious choices (why A over B, what looks wrong but is intentional). Don't document what code obviously does.

---

## 7. DON'T GUESS AT REQUIREMENTS

If a request is ambiguous, ask one clarifying question. Don't interpret "add search" as full-text with facets, filters, and analytics.

Ask: "Do you need X or is Y sufficient?"

---

## 8. NO AUTO-INSTALLING DEPENDENCIES

Never run install commands or modify dependency files without approval.

Before proposing a dependency, state: what it does, why existing stack can't handle it, maintenance status, transitive dependencies, runtime vs dev.

Prefer: stdlib over third-party, small utility function over library, script over permanent dependency.

---

## 9. WORKING STATE AT EVERY STEP

Every change should leave the system working. Incremental over big-bang. Feature flags over risky migrations. If replacing something, remove the old in the same change or document exactly when.

---

## 10. SCOPE YOUR CONFIDENCE

Be explicit about certainty:
- "I can see in the code that..." (verified)
- "Based on patterns here, I think..." (inference)
- "I'm not sure—verify this..." (guess)

Never present guesses as facts. Never claim something "should work" without explaining the assumption.

---

## 11. ASK BEFORE DELETING

Never delete files, functions, routes, columns, or endpoints without confirming. "Unused" code might be called dynamically, referenced externally, or part of an in-progress migration.

State your evidence, then wait for confirmation.

---

## 12. MATCH PROJECT PATTERNS

Check before writing: naming, quotes, indentation, test location, import style, error handling, logging. Match the file you're editing, not your preference.

---

## 13. COMMIT HYGIENE

Two artifacts per commit, different audiences:

**Commit message** (engineering-facing):
```
<type>: <specific summary>

<Technical reason/constraint. 1-2 sentences.>
```
Types: `fix`, `feat`, `refactor`, `docs`, `chore`, `test`

**Release notes** (customer-facing, append to `docs/RELEASE_NOTES.md`):
```markdown
## [YYYY-MM-DD] — <short description>

### What changed
- <user-visible impact first, then implementation>

### Why
<plain language, outcome-first>

### Files touched
- `path/file` — <what changed>
```

Rules: New entries at top. Plain language, minimal jargon. When adding utilities, explain what it does and why centralizing matters.

---

## 14. NO FILE SPRAWL

- Documentation only in `/docs/`
- No random `.md` files in root (except README, AGENTS, CHANGELOG)
- No files unless the feature requires them—prefer editing existing
- No scratch or temporary files

---

## 15. CONTEXT WINDOW DISCIPLINE

Read only files relevant to the current task. Read specific functions, not entire files "for background." Load examples when needed, not preemptively. Summarize and release context when it gets long.

---

## 16. GOAL-DRIVEN EXECUTION

Define verifiable success before starting:
- "Add validation" → "Write tests for invalid inputs, make them pass"
- "Fix the bug" → "Reproduce in a test, make it pass"

For multi-step work:
1. [Step] → verify: [check]
2. [Step] → verify: [check]

---

## PERSONAS

Load from `/skills/` by domain as needed: `@arch`, `@dev`, `@guard`, `@qa`, `@pm`, `@ux`, `@ops`, `@writer`, `@seo`, `@video`, `@perf`. See each file for scope and handoffs.

---

## CONVENTIONS

See `CONVENTIONS.md` for tech stack, code style, project structure, testing patterns, and known intentional quirks.