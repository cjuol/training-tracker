# Project Foundation Specification

## Purpose

Define el esqueleto reproducible del repo V2: estructura, arranque, pipeline de CI, boundaries entre módulos, y harness de tests. Todo lo que precede a la lógica de negocio.

## Requirements

### Requirement: Monorepo layout

The system MUST expose dos workspaces hermanos en la raíz: `app/` (Symfony 7.x / PHP 8.3) y `sidecar-garmin/` (FastAPI / Python 3.12+). La raíz SHALL contener `docker-compose.yml`, `Makefile`, `README.md`, `.env.example`, `.gitignore` y `CLAUDE.md`.

#### Scenario: Raíz expone workspaces y archivos de gobernanza

- GIVEN el repo recién clonado
- WHEN el usuario lista la raíz
- THEN `app/`, `sidecar-garmin/`, `docker/`, `docker-compose.yml`, `Makefile`, `README.md`, `.env.example`, `.gitignore`, `CLAUDE.md` existen

### Requirement: Módulos Symfony con boundaries

`app/src/` MUST contener exactamente 6 carpetas de módulo: `Shared`, `Training`, `Nutrition`, `Wearables`, `Analytics`, `Ingestion`. Cada módulo MUST declararse en `app/composer.json` bajo `autoload.psr-4` con el namespace `App\{Module}\`. Ningún módulo MUST importar clases de otro módulo excepto de `Shared`.

#### Scenario: Namespaces aislados por módulo

- GIVEN composer autoload regenerado
- WHEN se inspecciona `vendor/composer/autoload_psr4.php`
- THEN cada namespace `App\Training\`, `App\Nutrition\`, `App\Wearables\`, `App\Analytics\`, `App\Ingestion\`, `App\Shared\` mapea a `src/{Module}/`

#### Scenario: No cross-module imports

- GIVEN el código fuente de cualquier módulo
- WHEN se grep `^use App\\(Training|Nutrition|Wearables|Analytics|Ingestion)\\` desde otro módulo distinto a `Shared`
- THEN la búsqueda no devuelve resultados

### Requirement: Doctrine con mapping por módulo y prefijo de tabla

Doctrine ORM MUST configurarse con una conexión única y un mapping separado por módulo apuntando a `src/{Module}/Entity`. Toda entidad con tabla asociada MUST declarar nombre de tabla con prefijo del módulo en snake_case (ej: `training_*`, `wearables_*`). Ninguna entidad MUST declarar `ORM\JoinColumn` hacia otra entidad cuyo namespace raíz sea distinto al propio (excepto `Shared`).

#### Scenario: schema:validate sin errores con módulos vacíos

- GIVEN los 6 directorios `src/{Module}/Entity` vacíos o con stubs mínimos
- WHEN el usuario ejecuta `php bin/console doctrine:schema:validate --skip-sync`
- THEN el comando retorna exit code 0

### Requirement: Docker stack arranca

`docker-compose.yml` MUST definir cuatro servicios: `app` (PHP-FPM + nginx o Symfony CLI server), `postgres` (`postgres:16`), `redis`, `sidecar`. `make up` SHALL levantar los cuatro servicios y SHALL ser idempotente.

#### Scenario: Servicios healthy tras make up

- GIVEN el repo clonado con `.env` copiado de `.env.example`
- WHEN el usuario ejecuta `make up`
- THEN los servicios `app`, `postgres`, `redis`, `sidecar` están en estado `running`/`healthy` tras 60s

#### Scenario: App responde 200 en home

- GIVEN el stack Docker activo
- WHEN se hace GET a `http://localhost:8000/`
- THEN la respuesta tiene status 200

#### Scenario: Sidecar responde /health

- GIVEN el stack Docker activo
- WHEN se hace GET a `http://localhost:8001/health`
- THEN la respuesta tiene status 200 y body JSON `{"status":"ok"}`

### Requirement: PWA manifest y service worker servidos

`app/` MUST servir `/manifest.webmanifest` (Content-Type `application/manifest+json`) y `/sw.js` (JavaScript) desde AssetMapper. El service worker SHOULD registrar `workbox-background-sync` aunque la cola esté vacía en Fase 0.

#### Scenario: Manifest disponible

- GIVEN el stack activo
- WHEN se hace GET a `http://localhost:8000/manifest.webmanifest`
- THEN status 200 y el JSON incluye `name`, `short_name`, `start_url`, `display: standalone`

### Requirement: Harness de tests multi-runtime

`app/` MUST incluir Pest con al menos un test smoke verde. `sidecar-garmin/` MUST incluir pytest con al menos un test smoke verde del endpoint `/health`. `app/` MUST incluir Playwright con al menos un smoke E2E ejecutable localmente.

#### Scenario: Pest smoke pasa

- GIVEN `app/` con dependencias composer instaladas
- WHEN se ejecuta `cd app && vendor/bin/pest`
- THEN exit code 0 y al menos 1 test pasa

#### Scenario: pytest smoke pasa

- GIVEN `sidecar-garmin/` con dependencias pip instaladas
- WHEN se ejecuta `cd sidecar-garmin && pytest`
- THEN exit code 0 y al menos 1 test pasa

#### Scenario: Playwright smoke pasa local

- GIVEN stack Docker activo y `app/node_modules` instalado
- WHEN se ejecuta `cd app && npx playwright test`
- THEN exit code 0

### Requirement: CI GitHub Actions verde en primer push

`.github/workflows/ci.yml` MUST definir al menos dos jobs paralelos: `php-tests` (composer install + pest) y `python-tests` (pip install + pytest). El workflow SHALL disparar en push y pull_request sobre cualquier rama.

#### Scenario: CI verde al push inicial

- GIVEN el repo con commit inicial de Fase 0 pushed a GitHub
- WHEN GitHub Actions ejecuta el workflow `ci.yml`
- THEN ambos jobs `php-tests` y `python-tests` finalizan con status `success`
