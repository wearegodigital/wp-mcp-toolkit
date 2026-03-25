# WP MCP Toolkit — Development Process

## Context

### Key Principles

- "How you code this is just as important as what you're coding"
- Functionality matters, but readability and simplicity matter equally
- Prefer figuring out edge cases as they happen rather than coding 2 lines into 40
- Don't be tied into any single tool — modular abstractions everywhere
- Agents have freedom to implement, but simplification loops catch over-engineering
- Sean reviews documentation, not pre-approves changes

---

## 1. Phase Lifecycle

Every development phase follows this sequence:

```
1. PLAN         Read phase objectives from plan. Break into atomic tasks.
                Identify dependencies. Check file size targets.

2. EXECUTE      Agents implement tasks with full freedom.
                Per-task: implement -> php -l -> verify ability count.
                Write audit log entry after each task.
                If task fails: log retry_context, loop with enriched context.

3. VERIFY       Per-task gate (MANDATORY before "done"):
                - php -l on all modified files (zero errors)
                - WP-CLI ability count matches expected
                - Manual spot-check of functionality via MCP

4. SIMPLIFY     Invoke code-simplifier on all changed files.
                Review: is anything over-engineered? Can it be simpler?
                Re-run verification after any simplification changes.

5. DOCUMENT     Generate phase summary to docs/phases/{phase-name}.md.
                Includes: what was done, key decisions, files, line count.

6. AUDIT        Verify all tasks have audit log entries.
                Generate phase summary from audit log.

7. REVIEW       Sean reads phase documentation.
                Sean reviews key files if desired.
                Feedback incorporated before next phase.

8. COMMIT       Atomic commit per phase. Conventional commit message.
                feat: / fix: / refactor: / test: / docs: / chore:

9. NEXT PHASE   Begin from step 1 with next phase objectives.
```

---

## 2. Hard Rules

### Simplicity First

- Write the simplest code that solves the current problem. Not the hypothetical future problem.
- Prefer 2 lines over 40. If you catch yourself writing defensive code for cases that don't exist yet, stop.
- No abstraction before the third use. Write the specific thing first. Refactor when a pattern emerges.
- No "framework" code. Use WordPress APIs directly.

### DRY

- One function, one place. If used in multiple files, it lives in a shared module or the abstract base class.
- No copy-paste across files. Extract and import.
- If a pattern appears 3+ times across ability classes, extract to `class-abstract-abilities.php`.

### File Size Limits

- Ability files: 400 lines max. Split by domain if approaching.
- Infrastructure/helper files: 300 lines max. Split into modules.
- Admin view files: 300 lines max. Extract sections.
- These are hard limits. If a file exceeds them, split it before continuing.

### Readability

- Descriptive names over short names (except loop variables).
- Comments explain WHY, not WHAT. The code explains what.
- Group related methods together in classes.
- No barrel files that re-export everything.

### PHP Standards

- PHP 7.4+ for core toolkit. PHP 8.0+ for workspace module only.
- WordPress coding standards (tabs, braces on same line for functions, etc.).
- Return `WP_Error` on failure, never throw exceptions.
- Error codes always prefixed with `wpmcp_`.

### Error Handling

- `return new \WP_Error('wpmcp_code', 'Human-readable message')` — never throw.
- Never swallow errors silently.
- Check `is_wp_error()` on WordPress function returns before using.
- try/catch only at module boundaries where external code might throw.

### Security (Non-Negotiable)

- Sanitize ALL inputs: `sanitize_key()` for slugs, `absint()` for IDs, `sanitize_text_field()` for strings, `wp_kses_post()` for HTML.
- All `$wpdb` queries MUST use `$wpdb->prepare()`.
- All HTML output escaped: `esc_html()`, `esc_attr()`, `esc_url()`.
- No PII in API responses (filter `ip`, `user_agent`, `source_url`).
- Permission callbacks on every write ability.

### Before Claiming "Done" (MANDATORY)

Before marking ANY task complete, you MUST run and pass ALL of these:

1. `docker exec devkinsta_fpm php -l <file>` on every modified file — zero errors
2. `docker exec devkinsta_fpm wp eval "echo count(wp_get_abilities());" --path=/www/kinsta/public/generatormedia/ --allow-root --user=1` — count matches expected
3. If any fail, fix them before claiming completion. No exceptions.

This applies to every task, every phase, every agent.

---

## 3. When Sean Reviews

Sean reviews AFTER documentation is generated, not before code is written.

1. Phase completes (all tasks pass verification)
2. Code simplifier runs
3. Phase summary generated to `docs/phases/`
4. Sean reads the summary document
5. Sean optionally dives into specific files
6. Feedback is incorporated before next phase begins

Sean does NOT:
- Pre-approve task-level changes
- Review every commit in real-time
- Gate progress on reviews (except milestone boundaries)

Sean DOES:
- Review phase documentation after each phase
- Provide feedback that shapes the next phase
- Review the full workspace module at milestone completion

---

## 4. Audit Log System

### Technology: SQLite

SQLite at `.omc/audit.db`. Queryable, lightweight, no external service needed. Git-ignored.

### Schema

```sql
CREATE TABLE IF NOT EXISTS audit_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  timestamp TEXT NOT NULL DEFAULT (datetime('now')),
  phase TEXT NOT NULL,
  task TEXT NOT NULL,
  action TEXT NOT NULL,
  files_touched TEXT,
  status TEXT NOT NULL,
  description TEXT,
  retry_context TEXT,
  lines_added INTEGER,
  lines_removed INTEGER
);
```

### CLI Usage

```bash
# Log a completed task
.omc/audit.sh log --phase "workspace-1" --task "Create container class" --action "implemented" --files "includes/modules/workspace/class-workspace-container.php" --status "completed" --description "Container with path resolution and writable detection"

# Log a failure
.omc/audit.sh log --phase "workspace-1" --task "Create validator" --action "implemented" --files "class-workspace-validator.php" --status "failed" --retry-context "php -l failed: unexpected token on line 45"

# List entries
.omc/audit.sh list                        # all entries
.omc/audit.sh list --phase workspace-1    # filter by phase
.omc/audit.sh list --status failed        # show failures

# Phase summary
.omc/audit.sh summary --phase workspace-1
```

---

## 5. Phase Documentation

### Location

`docs/phases/` with one file per phase:

```
docs/phases/
  workspace-1-foundation.md
  workspace-2-wiring.md
  workspace-3-core-abilities.md
  ...
```

### Template

```markdown
# Phase [ID]: [Name]

**Date**: [completion date]
**Line count**: [total source lines after this phase]

## Goal

What this phase aimed to achieve.

## What Was Done

- **[Task 1]**: [summary] — files: `[list]`
- **[Task 2]**: [summary] — files: `[list]`

## Key Decisions

- Why we chose X over Y
- Any deviations from the plan and why

## File Inventory

| File | Lines | Purpose |
| ---- | ----- | ------- |
| ...  | ...   | ...     |

## Next Phase Preview

What is coming next and any dependencies.
```

---

## 6. QA Gate Enforcement

### Per-Task Gate

| Check | Command | Must Pass |
|-------|---------|-----------|
| PHP syntax | `docker exec devkinsta_fpm php -l <file>` | Zero errors |
| Ability count | WP-CLI `count(wp_get_abilities())` | Matches expected |
| Functionality | Call ability via MCP transport | Returns expected result |

### Per-Phase Gate

| Gate | What | Mechanism |
|------|------|-----------|
| Code simplifier | Over-engineering check | Anthropic code-simplifier agent |
| Documentation | Phase summary generated | Writer agent |
| Audit | All tasks logged | Audit log query |

### Enforcement Hierarchy

| Layer | What | When |
|-------|------|------|
| 1. CLAUDE.md | Syntax + ability count | Every task |
| 2. Code simplifier | Over-engineering check | Every phase |
| 3. Phase docs | What was done, key decisions | Every phase |
| 4. Sean review | Documentation review | Every phase |

---

## 7. What Goes Where

| Location | Purpose | Lifetime |
|----------|---------|----------|
| `CLAUDE.md` | Hard rules, the constitution | Permanent, versioned |
| `docs/plans/*.md` | Strategic plans and architecture | Reference, versioned |
| `docs/phases/*.md` | Phase summaries (generated) | Post-phase, versioned |
| `.omc/audit.db` | SQLite audit log | Development aid, git-ignored |
| `.omc/plans/*.md` | Working plans (OMC internal) | Session aid, git-ignored |
| `admin/` | Settings page, views, assets | Permanent, versioned |
