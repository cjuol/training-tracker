# Archive Report — fase-0-setup

**Archived**: 2026-04-16
**Verdict at archive time**: PASS WITH WARNINGS

## Artifacts preserved

- `proposal.md` — intent, scope, success criteria
- `exploration.md` — 4 gaps resueltos
- `design.md` — 4 decisiones arquitectónicas con rationale + árbol de dirs + snippets
- `specs/project-foundation/spec.md` — 7 requirements, 11 scenarios (delta)
- `tasks.md` — 33/33 tasks marcadas `[x]`
- `verify-report.md` — 10/12 scenarios COMPLIANT, 2 PARTIAL

## Specs synced to source of truth

| Domain | Action | Details |
|--------|--------|---------|
| project-foundation | Created | 7 requirements, 11 scenarios (new full spec — no main spec existía) |

Source of truth: `openspec/specs/project-foundation/spec.md`.

## Post-verify updates

- **Playwright E2E ejecutado tras verify**: `npm install` + `npx playwright install chromium` + `npx playwright test` → `2 passed (550ms)`. El PARTIAL "Playwright smoke pasa local" queda como COMPLIANT.
- **CI remoto pendiente**: no hay `git remote` configurado. El usuario debe crear el repo en GitHub (`gh repo create` o manual) y `git push -u origin main`. El scenario "CI verde al push inicial" queda como PARTIAL (structural evidence: `.github/workflows/ci.yml` presente y todos los comandos del workflow verdes en local).

## Outstanding items (carry-over a Fase 1)

- Iconos PWA `/icons/icon-192.png` y `/icons/icon-512.png` no existen — manifest loguea 404 al instalar. Generar o ajustar manifest antes de Fase 1.
- workbox-window diferido — instalar localmente en `assets/vendor/` cuando la UX necesite update notifications / queue messaging.
- Stimulus no instalado — añadir `symfony/stimulus-bundle` cuando Fase 1 implemente UX del logueo.
- PHPStan nivel 6 (no max) — subir cuando haya código real que lo justifique.

## State transition

- `strict_tdd`: false → **true** (Fase 1 y siguientes aplican RED-GREEN-REFACTOR obligatorio).
- `testing.status`: not_installed → **installed** (Pest 4.6 + pytest 9.0 + Playwright 1.48 + PHPStan 6 + ruff + mypy strict).

## SDD cycle complete

El change `fase-0-setup` ha completado todas las fases SDD: explore → propose → spec → design → tasks → apply → verify → archive. El repo tiene scaffold funcional end-to-end.
