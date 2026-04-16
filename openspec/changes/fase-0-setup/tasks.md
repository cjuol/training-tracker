# Tasks: Fase 0 — Setup monorepo

Scope: scaffolding. Entidades reales, logueo y Garmin viven en fases siguientes.

## Phase 1: Infraestructura Docker + Makefile

- [x] 1.1 Crear `docker/app.Dockerfile` (php:8.3-fpm base, extensiones pdo_pgsql, intl, zip, opcache, composer).
- [x] 1.2 Crear `docker/nginx.conf` (upstream php-fpm:9000, root `/app/public`, `try_files $uri /index.php$is_args$args`).
- [x] 1.3 Crear `sidecar-garmin/Dockerfile` (python:3.12-slim, `pip install -e '.[dev]'`, uvicorn).
- [x] 1.4 Crear `docker-compose.yml` con servicios `app`, `nginx`, `postgres:16`, `redis:7`, `sidecar`; volúmenes `pgdata`, `composer_cache`, `pip_cache`; red `ttnet`; healthchecks app + postgres.
- [x] 1.5 Crear `Makefile` con targets: `up`, `down`, `test`, `test-php`, `test-py`, `e2e`, `migrate`, `shell`, `logs`.
- [x] 1.6 Crear `.env.example` con `APP_ENV`, `APP_SECRET`, `DATABASE_URL`, `REDIS_URL`, `SIDECAR_SHARED_SECRET`, `GARMIN_*`. Copiar a `.env` en primer `make up`.

## Phase 2: Symfony scaffold

- [x] 2.1 `composer create-project symfony/skeleton:7.4.* app` dentro del repo. Eliminar `.git` interno.
- [x] 2.2 En `app/`: `composer require symfony/twig-bundle symfony/asset-mapper symfony/orm-pack symfony/messenger symfony/uid doctrine/doctrine-migrations-bundle` (framework-bundle y runtime ya vienen en skeleton).
- [x] 2.3 `composer require --dev pestphp/pest symfony/phpunit-bridge phpstan/phpstan` (pest-plugin-symfony no existe).
- [x] 2.4 Crear directorios `app/src/{Shared,Training,Nutrition,Wearables,Analytics,Ingestion}/Entity` con `.gitkeep`. Editar `app/composer.json` → `autoload.psr-4` con 6 namespaces. Eliminadas carpetas default `src/Controller`, `src/Entity`, `src/Repository`. `composer dump-autoload` ejecutado.
- [x] 2.5 Editar `app/config/packages/doctrine.yaml` con 6 mappings (`Shared`, `Training`, `Nutrition`, `Wearables`, `Analytics`, `Ingestion`). `auto_mapping: false`.
- [x] 2.6 `app/src/Shared/Controller/HomeController.php` con route `/` → renderiza `base.html.twig`. Namespace `App\Shared\Controller`.
- [x] 2.7 `app/templates/base.html.twig` con `<link rel="manifest">`, viewport meta, theme-color, importmap.

## Phase 3: PWA skeleton (Workbox + AssetMapper)

- [x] 3.1 `app/public/manifest.webmanifest` con `name`, `short_name`, `start_url: /`, `display: standalone`, `theme_color: #0a0a0a`, iconos 192/512 maskable. Nginx sirve con `application/manifest+json` (MIME añadido a `docker/nginx.conf`).
- [x] 3.2 `app/importmap.php` con entrypoints `app` y `sw-register`. workbox-window diferido a Fase 1 (AssetMapper no soporta `url` en importmap y jsDelivr data API devuelve 500 para workbox-window).
- [x] 3.3 `app/public/sw.js` estático: `importScripts` a Workbox 7.1 desde Google CDN, `backgroundSync.Queue('setlog-queue')` con retención 24h, `skipWaiting()` y `clients.claim()` en install/activate.
- [x] 3.4 `app/assets/sw-register.js` registra `/sw.js` con `navigator.serviceWorker.register` (plain JS). Stimulus diferido a Fase 1 cuando la UX lo requiera.

## Phase 4: Sidecar FastAPI

- [x] 4.1 `sidecar-garmin/pyproject.toml` con setuptools backend, deps fastapi/uvicorn[standard]/httpx/pydantic-settings, dev pytest+pytest-asyncio+ruff+mypy. pythonpath `src`, mypy strict.
- [x] 4.2 `sidecar-garmin/src/sidecar/main.py` con FastAPI + `GET /health` → `{"status":"ok","version":"0.1.0"}`.
- [x] 4.3 `sidecar-garmin/tests/test_health.py` con `fastapi.testclient.TestClient` (más simple que AsyncClient para GET sync). Healthcheck del compose reactivado.

## Phase 5: Harness tests + CI

- [x] 5.1 `app/tests/Pest.php` + `app/tests/Unit/SmokeTest.php` con `expect(1+1)->toBe(2)`. Pest 4 corre con `--testdox` (bug de output en non-TTY). Makefile actualizado. `phpstan.neon.dist` añadido (nivel 6).
- [x] 5.2 `app/package.json` con `@playwright/test`, `app/playwright.config.ts` apuntando a BASE_URL env (default http://localhost:8000), `app/tests/e2e/smoke.spec.ts` con 2 tests (home renders + manifest MIME). workbox-window diferido a F1.
- [x] 5.3 `.github/workflows/ci.yml` con jobs `php-tests` (composer cache + pest --testdox + phpstan) y `python-tests` (ruff + mypy + pytest) paralelos. E2E Playwright diferido a CI de F1.

## Phase 6: Docs + config repo

- [x] 6.1 `README.md` con brief + requisitos + arranque (make up/test) + estructura + boundaries + comandos + referencia a SDD + CI.
- [x] 6.2 `CLAUDE.md` raíz con stack, boundaries (reglas duras), convenciones código+commits, SDD flow, ficheros sensibles, gotchas (Pest testdox, importmap url).
- [x] 6.3 `.gitignore` raíz: `/.env` anclado a root (app/.env sí se commitea, convención Symfony). `node_modules`, `.venv`, `.egg-info`, pytest/mypy/ruff caches, playwright reports ya cubiertos. app/vendor y app/var los cubre app/.gitignore del skeleton.

## Phase 7: Verificación

- [ ] 7.1 `make up` → 4 servicios healthy tras 60s (spec "Servicios healthy tras make up").
- [ ] 7.2 `curl localhost:8080/` → 200 (spec "App responde 200 en home") + `curl localhost:8080/manifest.webmanifest` → 200 JSON (spec "Manifest disponible").
- [ ] 7.3 `curl localhost:8001/health` → `{"status":"ok"}` (spec "Sidecar responde /health").
- [ ] 7.4 `make test` verde y `docker compose exec app php bin/console doctrine:schema:validate --skip-sync` exit 0 (spec "schema:validate sin errores").
- [ ] 7.5 `grep -rE '^use App\\(Training|Nutrition|Wearables|Analytics|Ingestion)\\' app/src/{Training,Nutrition,Wearables,Analytics,Ingestion}/ | grep -v Shared` → sin resultados (spec "No cross-module imports").
- [ ] 7.6 Push a GitHub → CI workflow en verde (spec "CI verde al push inicial"). Flip `strict_tdd: true` en `openspec/config.yaml` y actualizar `testing.status: installed`.
- [ ] 7.7 Ejecutar `sdd-verify` para validar implementación contra el spec. Si pasa: `sdd-archive`.
