# CLAUDE.md — Training Tracker

Instrucciones específicas del repo. Se mezclan con `~/.claude/CLAUDE.md` (persona Gentleman, Engram, SDD orchestrator).

## Stack

- **app/**: Symfony 7.4 LTS · PHP 8.3 · Doctrine ORM · Messenger · AssetMapper · Twig · Pest 4 · PHPStan nivel 6
- **sidecar-garmin/**: Python 3.12 · FastAPI · uvicorn[standard] · pytest · ruff · mypy strict
- **Infra**: docker-compose (app, nginx, postgres:16-alpine, redis:7-alpine, sidecar) · GitHub Actions CI (jobs PHP + Python paralelos)
- **Frontend**: PWA con Service Worker + Workbox BackgroundSync (Workbox desde Google CDN vía `importScripts`)

## Boundaries de módulos (REGLAS DURAS)

- Módulos: `Shared`, `Training`, `Nutrition`, `Wearables`, `Analytics`, `Ingestion`.
- Cada módulo vive en `app/src/{Module}/` con namespace `App\{Module}\`.
- **Prohibido**: importar clases de otro módulo salvo `App\Shared\*`. `sdd-verify` hace grep.
- **Prohibido**: `ORM\JoinColumn` cruzando namespace raíz (excepto hacia `Shared`). Usá snapshots de valor o eventos Messenger.
- Tablas con prefijo explícito en `#[ORM\Table(name: '{module}_xxx')]` — nunca confiar en naming strategy.
- Una conexión Doctrine, 6 mappings explícitos (`auto_mapping: false`).

## Convenciones de código

- PHP 8.3 idiomas: `readonly`, enums, `declare(strict_types=1)`, typed props, final by default.
- Controllers thin (`AbstractController`), services hacen el trabajo. DI por constructor.
- Sin abstracciones para un solo uso. Sin DTOs dentro del mismo módulo.
- Python: type hints en todo (`mypy strict` lo exige). Async por defecto en FastAPI handlers.

## Comandos frecuentes

```
make up              # arranca stack
make test            # pest + pytest
make migrate         # doctrine migrate
docker compose exec app composer require ...
docker compose exec app bin/console ...
docker compose exec sidecar pytest
```

## Trabajo con SDD (OpenSpec)

Cualquier cambio no trivial (feature nueva, refactor cross-module, cambio de schema, nueva dependencia) pasa por SDD:

1. `/sdd-new <nombre-en-kebab>` en modo `interactive` + `openspec`.
2. Fases: `explore → propose → spec + design (paralelo) → tasks → apply → verify → archive`.
3. Artefactos en `openspec/changes/{change-name}/`. Se commitean junto al código.

Cambios triviales (bug fix 1-3 líneas, rename local, test aislado) pueden saltarse SDD.

## Convenciones de commits

Conventional Commits en español:
- `feat(scope): ...` — nueva funcionalidad
- `fix(scope): ...` — bug fix
- `refactor(scope): ...`, `docs(...)`, `test(...)`, `chore(...)`, `ci(...)`, `perf(...)`
- Sujeto imperativo, <72 chars, sin punto final, sin Co-Authored.
- Scopes habituales: `app`, `infra`, `sidecar`, `pwa`, `sdd`, `test`, `ci`.

**Nunca**: `--no-verify`, `--amend` sobre commits publicados, `push --force` a main.

## Ficheros sensibles

- `/.env` en raíz → contiene secretos de docker-compose (SIDECAR_SHARED_SECRET, Garmin creds). Gitignored, NUNCA commitear.
- `app/.env` → defaults de Symfony, seguro commitear (sin secretos reales).
- `app/.env.local` → overrides locales, gitignored por convención Symfony.

## Estado del proyecto

- Fase 0 (scaffold) — en curso / cerrada según último archive.
- Fase 1 (logueo offline de sesiones) — siguiente.
- Fase 2+ descritas en el plan de v2 (ver conversaciones iniciales / engram).

## Testing

- `strict_tdd: false` hasta que todos los runners estén instalados. Al cerrar Fase 0, flip a `true` en `openspec/config.yaml` → RED-GREEN-REFACTOR obligatorio desde Fase 1.
- Pest 4 tiene bug de output en non-TTY: Makefile usa `--testdox`.
- E2E Playwright local en Fase 0; se añade al CI en Fase 1.

## No hacer

- No meter entidades en `App\Entity` o `App\Repository` (son de módulo).
- No usar `auto_mapping: true` en Doctrine — rompería los boundaries.
- No importar workbox-window vía importmap `url` (AssetMapper no lo soporta); si hace falta, descargarlo localmente a `assets/vendor/`.
- No instalar dependencias "por si acaso". Si no hay uso real, fuera.
