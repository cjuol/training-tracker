# Exploration: fase-0-setup

Greenfield. No existing codebase to investigate. Design doc (en raíz del proyecto, sección "Training Tracker v2 — Rediseño completo") cubre stack, módulos, modelo de datos, UX y fases. Esta exploración solo resuelve los 4 gaps operacionales para scaffolding.

## Current State

Repo vacío. `.git` inicializado sin commits. `openspec/config.yaml` ya declara stack planeado y `strict_tdd: false` hasta que Fase 0 aterrice el harness de tests.

## Gap 1 — Layout monorepo Symfony + sidecar Python

### Approaches

1. **Carpetas hermanas `app/` + `sidecar-garmin/` + `docker/` en raíz** (propuesto por doc)
   - Pros: boundaries claras, composer.json aislado en `app/`, pyproject.toml aislado en `sidecar-garmin/`, docker-compose en raíz orquesta todo.
   - Cons: dev flow requiere `cd app` para comandos Symfony. Mitigable con Makefile en raíz (`make test`, `make migrate`).
   - Effort: Low.

2. **Symfony en raíz + sidecar en subdirectorio**
   - Pros: comandos Symfony sin `cd`.
   - Cons: mezcla composer.json/pyproject.toml en raíz, contamina gitignore, confunde herramientas (IDE, CI).
   - Effort: Low, pero peor mantenimiento.

**Recomendación**: Approach 1. Makefile raíz aglutina los comandos frecuentes.

### Autoload PSR-4 por módulo

`app/composer.json`:
```json
"autoload": {
  "psr-4": {
    "App\\Shared\\":     "src/Shared/",
    "App\\Training\\":   "src/Training/",
    "App\\Nutrition\\":  "src/Nutrition/",
    "App\\Wearables\\":  "src/Wearables/",
    "App\\Analytics\\":  "src/Analytics/",
    "App\\Ingestion\\":  "src/Ingestion/"
  }
}
```

Dentro de cada módulo, estructura **flat al principio**: `Entity/`, `Repository/`, `Controller/`, `Service/`, `Message/`, `MessageHandler/`. No aplicar DDD-tactical (Domain/Application/Infrastructure) hasta que haya dolor real. Karpathy: no abstracciones especulativas.

## Gap 2 — spomky-labs/pwa-bundle vs Workbox artesanal

### Approaches

1. **spomky-labs/pwa-bundle solo**
   - Pros: manifest + SW generados desde YAML, integración AssetMapper nativa.
   - Cons: SW por defecto es cache-first estático. Añadir lógica de `background-sync` para colas de `SetLog` pendientes requiere ejector o plugin, y es frágil.
   - Effort: Low (arranque), High (extender).

2. **Workbox solo, SW escrito a mano**
   - Pros: control total. `workbox-background-sync` encaja exactamente con el contrato offline del doc (§5 y §11).
   - Cons: más boilerplate, manifest a mano.
   - Effort: Medium.

3. **Híbrido: pwa-bundle para manifest/install prompt + SW custom con workbox-core**
   - Pros: lo trivial (manifest) lo gestiona el bundle; lo crítico (background-sync de series) es custom.
   - Cons: dos mental models conviviendo.
   - Effort: Medium.

**Recomendación**: Approach 2 (Workbox solo). Razón: el manifest son 10 líneas JSON, no justifica la segunda dependencia. Background-sync es el core de la UX offline y debe ser legible/debuggable. Symfony AssetMapper sirve el SW como asset normal (`sw.js`). Registro manual en un Stimulus controller.

## Gap 3 — Harness test minimal en commit 1

Objetivo: CI verde desde el primer commit, con un test trivial por runner para que cualquier regresión futura falle ruidosamente.

### Pest (PHP)
- `composer require --dev pestphp/pest pestphp/pest-plugin-symfony symfony/phpunit-bridge`
- `app/tests/Unit/SmokeTest.php` con `it('arithmetic sanity', fn () => expect(1 + 1)->toBe(2));`
- `app/tests/Integration/` vacío hasta Fase 1.
- Command: `cd app && vendor/bin/pest`.

### pytest (sidecar)
- `sidecar-garmin/pyproject.toml` con `pytest`, `pytest-asyncio`, `httpx`, `ruff`, `mypy` en `[project.optional-dependencies].dev`.
- `sidecar-garmin/tests/test_smoke.py` con test del endpoint `/health` (endpoint real: retorna `{"status":"ok"}`).
- Command: `cd sidecar-garmin && pytest`.

### Playwright (PWA)
- `app/package.json` con `@playwright/test` en devDependencies (también `workbox-window`).
- `app/tests/e2e/smoke.spec.ts` que levanta la home y verifica el status `200`. En Fase 0 la home es un template Twig mínimo.
- Command: `cd app && npx playwright test`.
- Decisión: Playwright requiere Node. AssetMapper de Symfony NO requiere Node en runtime, pero Playwright y workbox-window sí en dev/CI.

### CI (GitHub Actions)
- Un workflow `ci.yml` con 3 jobs paralelos: `php`, `python`, `e2e`.
- `e2e` levanta Postgres + app via docker-compose antes de correr Playwright.
- Fase 0 acepta jobs con 1 test cada uno — el objetivo es el pipeline, no cobertura.

### strict_tdd
Una vez los 3 runners están en verde, flip a `true` en `openspec/config.yaml`. Esto habilita RED-GREEN-REFACTOR obligatorio en fases siguientes.

## Gap 4 — Migraciones Doctrine con módulos

### Approaches

1. **Una conexión, múltiples mappings por namespace, prefijo de tabla por módulo**
   - Pros: extraíble a microservicio en el futuro (cada tabla lleva su "dueño" en el nombre). Una sola conexión, sin coordinar TX distribuidas.
   - Cons: ninguna relevante para single-user.
   - Effort: Low.

2. **Una conexión, un solo mapping global, sin prefijo**
   - Pros: mínimo config.
   - Cons: ownership invisible. Un refactor de módulo toca tablas sin dueño aparente.
   - Effort: Low.

3. **Múltiples entity managers (uno por módulo)**
   - Pros: aislamiento total.
   - Cons: consultas cross-module forzadas a través de servicio, TX cross-module imposibles sin orquestación. Overkill para single-user.
   - Effort: High.

**Recomendación**: Approach 1.

### Config concreta

`app/config/packages/doctrine.yaml`:
```yaml
doctrine:
  orm:
    auto_generate_proxy_classes: true
    enable_lazy_ghost_objects: true
    mappings:
      Shared:     { is_bundle: false, type: attribute, dir: '%kernel.project_dir%/src/Shared/Entity',     prefix: 'App\Shared\Entity',     alias: Shared }
      Training:   { is_bundle: false, type: attribute, dir: '%kernel.project_dir%/src/Training/Entity',   prefix: 'App\Training\Entity',   alias: Training }
      Nutrition:  { is_bundle: false, type: attribute, dir: '%kernel.project_dir%/src/Nutrition/Entity',  prefix: 'App\Nutrition\Entity',  alias: Nutrition }
      Wearables:  { is_bundle: false, type: attribute, dir: '%kernel.project_dir%/src/Wearables/Entity',  prefix: 'App\Wearables\Entity',  alias: Wearables }
      Analytics:  { is_bundle: false, type: attribute, dir: '%kernel.project_dir%/src/Analytics/Entity',  prefix: 'App\Analytics\Entity',  alias: Analytics }
      Ingestion:  { is_bundle: false, type: attribute, dir: '%kernel.project_dir%/src/Ingestion/Entity',  prefix: 'App\Ingestion\Entity',  alias: Ingestion }
```

### Prefijo de tabla

Convención: `training_mesocycle`, `nutrition_meal_option`, `wearables_health_metric`, etc. Enforced vía `#[ORM\Table(name: 'training_mesocycle')]` en cada entidad — no se usa naming strategy global (explícito > mágico).

### FKs cross-module

PROHIBIDAS por convención. Ejemplo: `Session` en Training NO tiene FK a `TrainingReadiness` en Wearables. En su lugar:
- `Session.readinessScoreAtStart` es un snapshot `int` nullable (valor, no referencia).
- Comunicación se hace por Symfony Messenger events síncronos (memoria) entre módulos.

Regla verificable: `sdd-verify` debe grep `ORM\JoinColumn` y fallar si cruza namespaces.

### Carpeta de migraciones

Una sola: `app/migrations/`. Doctrine Migrations Bundle estándar. Convención de nombre: `VersionYYYYMMDDHHMMSS_module_action.php` (ej: `Version20260420093000_training_create_mesocycle_table.php`).

## Recommendation

Proceder a `sdd-propose` con las decisiones de esta exploración consolidadas. El propose debe producir un plan de Fase 0 que incluya:

1. Scaffolding monorepo (app/ + sidecar-garmin/ + docker/ + Makefile + CI).
2. Symfony 7.x con AssetMapper (sin Webpack), Doctrine configurado con mappings por módulo, migración vacía de verificación.
3. Sidecar FastAPI stub con `/health` y Dockerfile.
4. PWA: manifest + SW skeleton con Workbox background-sync wireado pero sin cola real (llenará Fase 1).
5. Harness Pest + pytest + Playwright con 1 smoke test cada uno.
6. GitHub Actions CI con 3 jobs paralelos.
7. README mínimo (cómo arrancar localmente).
8. `.env.example` con variables documentadas.

## Risks

- **Playwright E2E en CI requiere docker-compose up previo** — complica el workflow. Mitigación: dejar E2E para ejecución local en Fase 0 y añadirlo a CI en Fase 1 cuando haya UI real que probar.
- **AssetMapper + Workbox no tienen integración oficial** — el SW se sirve como asset estático, pero imports de `workbox-window` requieren o bundle manual o CDN. Mitigación: usar `importmap` de AssetMapper y apuntar a `workbox-window` en jsDelivr, o empaquetar a mano con esbuild.
- **`python-garminconnect` login con MFA en CI** — no hay CI que lo necesite en Fase 0 (el sidecar no se testea contra Garmin real), pero conviene dejar el `.env.example` marcando MFA como manual.
- **Node en dev es opcional para Symfony, obligatorio para Playwright** — documentar en README que sin Node no hay E2E local, pero el resto del dev flow funciona.

## Ready for Proposal

Yes. Exploration suficiente para que `sdd-propose` emita un plan de Fase 0 accionable sin ambigüedad.
