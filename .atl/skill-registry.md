# Skill Registry — training-tracker

Generated: 2026-04-16. Scope: user-level skills at `~/.claude/skills/`.
Regenerate with `/skill-registry` when new skills installed.

## User Skills

| Skill | Trigger context | When to inject |
|-------|-----------------|----------------|
| `karpathy-guidelines` | Writing, reviewing, or refactoring code | ALWAYS — applies to every code-writing delegation |
| `branch-pr` | Creating a pull request | Before opening any PR |
| `issue-creation` | Creating GitHub issues / bug reports / feature requests | When filing issues |
| `judgment-day` | User says "judgment day", "dual review", "juzgar" | Adversarial review only on request |
| `nothing-design` | User explicitly says "Nothing style" | Never auto-inject |
| `skill-creator` | Creating new skills | When authoring skills |
| `skill-registry` | Updating this registry | On skill changes |
| `go-testing` | Writing Go tests | N/A (no Go in this project) |

## Project Conventions

Sources scanned:
- `~/.claude/CLAUDE.md` — global user instructions (Gentleman persona, Engram protocol, SDD orchestrator, task log CSV)
- `CLAUDE.md` in repo root — NOT PRESENT (expected to be added during Fase 0)
- `AGENTS.md`, `GEMINI.md`, `.cursorrules` — NOT PRESENT

## Compact Rules (inject into every code-writing sub-agent)

### From karpathy-guidelines (ALWAYS inject)
- **Think before coding**: state assumptions, ask when uncertain, surface tradeoffs.
- **Simplicity first**: minimum code that solves the problem. No speculative features, abstractions, or error handling for impossible scenarios.
- **Surgical changes**: touch only what's needed. Don't refactor adjacent code. Match existing style.
- **Goal-driven**: define success criteria before implementing.

### From global CLAUDE.md · Gentleman persona (ALWAYS inject for this user)
- Tone: senior architect, direct, no hedging, no "sure/of course". Discrepar temprano y claro.
- Idioma: español por defecto. Inglés solo si repo/issues/commits ya están en inglés.
- Antes de implementar cambio no trivial: proponer enfoque 3-6 líneas y esperar OK.
- Conventional Commits obligatorio: `tipo(scope): sujeto` <72 chars imperativo.
- Nunca `--no-verify`, `--amend` sobre publicados, `push --force` a main/master/develop.
- No overengineering: librería solo si problema >30 líneas con stdlib/framework base.
- Patrones (DI, factory, repository genérico) solo con >2 usos reales.
- DTOs solo cuando cruzan bounded context.

### From global CLAUDE.md · Engram protocol (ALWAYS inject)
- Save proactively to engram after: decision, bug fix, convention established, discovery, gotcha.
- Format: What / Why / Where / Learned. Use `topic_key` for evolving topics.
- At session close: call `mem_session_summary` with Goal / Discoveries / Accomplished / Next Steps / Relevant Files.

### From global CLAUDE.md · Task log CSV (inject on session close)
- At end of substantive session, append row to `./.tasks-log.csv` (UTF-8, comma-sep, header `project,task,start,end,note`).
- Dedup by (project, start). One row per discrete task, not per session.
- Gitignore the file.

## Injection Policy

For every code-writing delegation in this project, orchestrator MUST inject:
1. karpathy-guidelines compact rules
2. Gentleman persona rules
3. Engram protocol rules

Additional skills inject only when their triggers match the delegation context.
