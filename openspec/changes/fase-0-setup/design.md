# Design: Fase 0 — Setup monorepo

## Technical Approach

Symfony 7.4 LTS + PHP 8.3 en `app/` con AssetMapper (sin Node para Symfony). Sidecar FastAPI en `sidecar-garmin/`. Docker Compose orquesta Postgres 16 + Redis + app + sidecar. PSR-4 un namespace por módulo. Doctrine una conexión, 6 mappings, prefijo de tabla. PWA con SW a mano usando `workbox-background-sync` (vía importmap → jsDelivr). CI GitHub Actions 2 jobs paralelos (PHP + Python). Sin lógica de negocio — solo terreno listo.

## Árbol de directorios (post-Fase 0)

```
training-tracker/
├── app/
│   ├── bin/console
│   ├── composer.json
│   ├── config/
│   │   ├── bundles.php
│   │   ├── packages/ (doctrine.yaml, messenger.yaml, asset_mapper.yaml, ...)
│   │   ├── routes.yaml
│   │   └── services.yaml
│   ├── importmap.php
│   ├── migrations/            (vacío, con .gitkeep)
│   ├── public/
│   │   ├── index.php
│   │   ├── manifest.webmanifest
│   │   └── sw.js              (generado o estático, ver decisión SW)
│   ├── src/
│   │   ├── Kernel.php
│   │   ├── Shared/            (Entity/, Controller/, ...)
│   │   ├── Training/
│   │   ├── Nutrition/
│   │   ├── Wearables/
│   │   ├── Analytics/
│   │   └── Ingestion/
│   ├── templates/base.html.twig
│   ├── tests/
│   │   ├── Pest.php
│   │   ├── Unit/SmokeTest.php
│   │   └── e2e/smoke.spec.ts
│   ├── phpunit.xml.dist
│   ├── playwright.config.ts
│   └── package.json           (solo devDeps: @playwright/test, workbox-window)
├── sidecar-garmin/
│   ├── pyproject.toml
│   ├── src/sidecar/
│   │   ├── __init__.py
│   │   ├── main.py            (FastAPI app, /health)
│   │   └── settings.py
│   ├── tests/test_health.py
│   └── Dockerfile
├── docker/
│   ├── app.Dockerfile
│   └── nginx.conf             (si se opta por php-fpm+nginx)
├── .github/workflows/ci.yml
├── .env.example
├── .gitignore
├── CLAUDE.md
├── Makefile
├── README.md
├── docker-compose.yml
└── openspec/                  (ya existente)
```

## Docker Compose

```
┌─────────────┐     ┌──────────────┐     ┌────────────┐
│ app         │────▶│ postgres:16  │     │ redis      │
│ :8000       │     │ :5432        │     │ :6379      │
└─────────────┘     └──────────────┘     └────────────┘
       │                    ▲                  ▲
       └────────────────────┴──────────────────┘
                             │
                      ┌──────────────┐
                      │ sidecar      │
                      │ :8001        │
                      └──────────────┘
Network: ttnet (bridge). Volumes: pgdata, composer_cache, pip_cache.
```

Servicios: `app` (expone 8080), `sidecar` (expone 8001), `postgres` (interno 5432, vol `pgdata`), `redis` (interno 6379). Healthchecks en app y postgres. `depends_on` con `condition: service_healthy`.

## Snippets clave

**`app/composer.json` autoload**:
```json
"autoload": {
  "psr-4": {
    "App\\Shared\\": "src/Shared/",
    "App\\Training\\": "src/Training/",
    "App\\Nutrition\\": "src/Nutrition/",
    "App\\Wearables\\": "src/Wearables/",
    "App\\Analytics\\": "src/Analytics/",
    "App\\Ingestion\\": "src/Ingestion/"
  }
}
```

**Doctrine mappings** → ya definido en `exploration.md` §Gap 4. Copiar literal a `app/config/packages/doctrine.yaml`.

**Makefile targets**:
```
up:       docker compose up -d --wait
down:     docker compose down
test:     $(MAKE) test-php && $(MAKE) test-py
test-php: docker compose exec app vendor/bin/pest
test-py:  docker compose exec sidecar pytest
e2e:      cd app && npx playwright test
migrate:  docker compose exec app bin/console doctrine:migrations:migrate -n
shell:    docker compose exec app bash
logs:     docker compose logs -f
```

**`.env.example`**:
```
APP_ENV=dev
APP_SECRET=change-me
DATABASE_URL=postgresql://app:app@postgres:5432/training_tracker?serverVersion=16
REDIS_URL=redis://redis:6379
SIDECAR_SHARED_SECRET=change-me
GARMIN_EMAIL=
GARMIN_PASSWORD=
GARMIN_MFA=manual
```

**`.github/workflows/ci.yml`** (esqueleto):
```yaml
name: CI
on: [push, pull_request]
jobs:
  php-tests:
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3', coverage: none, tools: composer:v2 }
      - run: composer install --no-progress
        working-directory: app
      - run: vendor/bin/pest
        working-directory: app
  python-tests:
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-python@v5
        with: { python-version: '3.12' }
      - run: pip install -e '.[dev]'
        working-directory: sidecar-garmin
      - run: pytest
        working-directory: sidecar-garmin
```

## Architecture Decisions

### Decision: AssetMapper vs Webpack/Vite

**Choice**: AssetMapper con importmap.
**Alternatives**: Webpack Encore, Vite.
**Rationale**: AssetMapper es stock en Symfony 7.x, no requiere Node para servir assets en prod. Playwright y workbox-window necesitan Node solo en dev. Menos superficie = menos roturas. Evita duplicar pipeline de build.

### Decision: Workbox solo vs spomky-labs/pwa-bundle

**Choice**: Workbox puro, SW escrito a mano, `workbox-background-sync` vía importmap → jsDelivr.
**Alternatives**: pwa-bundle (cubre manifest + SW) o híbrido.
**Rationale**: el SW es crítico para cola offline de `SetLog` en Fase 1. Control total > automágico. Manifest son 10 líneas JSON estático, no justifica la segunda dep.

### Decision: Mono-DB con prefijo de tabla vs múltiples entity managers

**Choice**: Una conexión, 6 mappings, prefijo `{module}_*`.
**Alternatives**: 6 entity managers, uno por módulo.
**Rationale**: single-user, TX cross-módulo triviales, extraíble a microservicio el día que haga falta (tablas ya marcadas con dueño). Múltiples EMs son overkill y complican Messenger + migraciones.

### Decision: FKs cross-module prohibidas

**Choice**: No `ORM\JoinColumn` entre módulos (salvo hacia `Shared`). Comunicación vía Symfony Messenger events síncronos.
**Alternatives**: FKs libres entre módulos (rápido pero acopla).
**Rationale**: boundaries enforced by code review y `sdd-verify` (grep). Permite extraer módulo a microservicio sin migrar schema ajeno. Snapshots de valor (ej. `Session.readinessScoreAtStart = int`) sustituyen a FKs por referencia.

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/**` | Create | Symfony project + módulos vacíos + AssetMapper + Pest |
| `sidecar-garmin/**` | Create | FastAPI stub + pytest |
| `docker/**` + `docker-compose.yml` | Create | Stack 4 servicios |
| `.github/workflows/ci.yml` | Create | CI paralelo |
| `Makefile`, `README.md`, `.env.example`, `CLAUDE.md` | Create | Dev flow + docs |
| `.gitignore` | Modify | Añadir `vendor/`, `node_modules/`, `.venv/`, `var/` |

## Interfaces / Contracts

**Sidecar `/health`**:
```
GET /health → 200 {"status":"ok","version":"0.1.0"}
```

**Eventos cross-module** (Messenger): nombres bajo `App\Shared\Event\`. Ninguno en Fase 0 — solo el contrato queda listo. Ejemplo futuro: `App\Shared\Event\SessionCompleted`.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit (PHP) | Smoke aritmético | Pest `expect(1+1)->toBe(2)` |
| Unit (Python) | Smoke import | pytest `def test_smoke(): assert True` |
| Integration (Python) | `/health` endpoint | pytest + httpx TestClient contra FastAPI |
| E2E (PWA) | Home 200, manifest servido | Playwright smoke local (CI en F1) |

No strict TDD en Fase 0 (greenfield, tests se escriben después del scaffold). Flip a strict_tdd tras landing.

## Migration / Rollout

No migration required. Repo vacío. Se integra a `main` vía un único PR "feat: fase-0 scaffold" (o varios commits atómicos bajo la misma rama `feat/fase-0-setup`).

## Resolved Decisions

- **Web server**: php-fpm 8.3 + nginx en Docker. Rationale: paridad con prod. Añade `docker/nginx.conf` y `docker/app.Dockerfile` con php-fpm.
- **Symfony version**: 7.4 LTS. Rationale: app va a prod, LTS > latest.

## Open Questions

None.
