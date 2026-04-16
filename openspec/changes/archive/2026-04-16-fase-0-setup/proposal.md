# Proposal: Fase 0 — Setup monorepo

## Intent

Arrancar el repo V2 con un esqueleto funcional end-to-end que compile, corra en Docker y pase CI desde el commit 1. Sin lógica de negocio. Objetivo: que al final de Fase 0 cualquier persona (hoy: el autor) haga `git clone && make up && make test` y vea los 3 runners en verde.

## Scope

### In Scope
- Monorepo con `app/` (Symfony 7.x + PHP 8.3), `sidecar-garmin/` (FastAPI), `docker/`, `Makefile`, `.env.example`.
- Symfony con 6 módulos vacíos (`Shared`, `Training`, `Nutrition`, `Wearables`, `Analytics`, `Ingestion`), PSR-4 por namespace, Doctrine configurado con mapping por módulo.
- Stack Docker: `app`, `postgres:16`, `redis`, `sidecar`. `docker-compose.yml` orquesta.
- PWA skeleton: AssetMapper + manifest JSON + Service Worker con Workbox `background-sync` wireado sin cola real.
- Harness test: Pest smoke + pytest smoke + Playwright smoke (local en F0, CI en F1).
- CI GitHub Actions: workflow `ci.yml` con jobs paralelos `php-tests` + `python-tests`.
- `README.md` mínimo (arranque, comandos, estructura), `.gitignore`, `CLAUDE.md` del repo.

### Out of Scope
- Logueo de sesiones (Fase 1).
- Modelos de datos reales (`Mesocycle`, `Session`, `SetLog`, etc.) — solo stubs si son necesarios para que Doctrine arranque.
- Integración Garmin (Fase 3), parser PDF (Fase 5), Analytics (Fase 4).
- Autenticación / OAuth (mono-usuario, diferido).
- Migración de datos del repo viejo.

## Capabilities

### New Capabilities
- `project-foundation`: estructura monorepo, arranque local reproducible, pipeline CI, harness de tests multi-runtime. Define QUÉ debe existir y cómo se verifica.

### Modified Capabilities
- None (greenfield).

## Approach

Seguir el layout y decisiones resueltas en `exploration.md` (layout carpetas hermanas, Workbox solo, Doctrine con mappings por módulo y prefijo de tabla, FKs cross-module prohibidas). Symfony via `symfony new --webapp=false` ajustado a 7.x. Sidecar con template FastAPI mínimo. AssetMapper sobre Webpack (menos Node en dev).

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/` | New | Symfony project, módulos vacíos, AssetMapper, Doctrine config |
| `sidecar-garmin/` | New | FastAPI stub con `/health` |
| `docker/` + `docker-compose.yml` | New | 4 servicios orquestados |
| `.github/workflows/ci.yml` | New | Jobs paralelos PHP + Python |
| `Makefile`, `README.md`, `.env.example`, `CLAUDE.md` | New | Dev flow y docs mínimas |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| AssetMapper + Workbox import sin integración oficial | Med | importmap apunta a jsDelivr; fallback esbuild si falla |
| Playwright CI inestable en F0 | Med | Diferir Playwright a CI de F1, correr local en F0 |
| Over-scaffolding (abstracciones sin uso) | Med | Módulos flat (`Entity/`, `Controller/`), sin DDD tactical |

## Rollback Plan

Fase 0 es aditiva sobre repo vacío. Rollback = `git reset --hard HEAD~N` sobre commits de la fase (main branch sin deploy, sin datos). No hay migraciones productivas que revertir.

## Dependencies

- PHP 8.3, Composer 2.x, Python 3.12+, Docker Desktop / Engine, Node 20+ (solo para Playwright local).
- Sin dependencias externas (Garmin API no se toca en F0).

## Success Criteria

- [ ] `make up` levanta los 4 servicios sin errores.
- [ ] `make test` corre Pest + pytest en verde.
- [ ] `npx playwright test` corre el smoke E2E local en verde.
- [ ] CI de GitHub Actions verde en el primer push.
- [ ] Symfony arranca en `http://localhost:8080` y devuelve 200 en `/`.
- [ ] Sidecar devuelve `{"status":"ok"}` en `http://localhost:8001/health`.
- [ ] `doctrine:schema:validate` no arroja errores con módulos vacíos.
