# Training Tracker

App mono-usuario offline-first para loggear entrenos de gimnasio con una mano durante la sesión, cruzar biométricos del Garmin Fénix 7 Pro y analizar progresión. Monolito modular Symfony + PWA + sidecar Python para Garmin Connect.

## Requisitos

- Docker Engine 24+ con Compose v2
- Node 20+ (solo para Playwright E2E local; opcional)
- Puerto 8000 libre (nginx) y 8001 libre (sidecar FastAPI)

Todo el resto (PHP 8.3, Python 3.12, Postgres 16, Redis 7) corre en containers.

## Arranque

```bash
git clone git@github.com:cjuol/training-tracker.git
cd training-tracker
cp .env.example .env            # edita APP_SECRET y SIDECAR_SHARED_SECRET si no es local
make up                         # levanta app, nginx, postgres, redis, sidecar
make test                       # corre Pest + pytest
```

Abrir `http://localhost:8000` → `Training Tracker · Fase 0 — scaffold OK.`
Health del sidecar: `curl http://localhost:8001/health`.

## Estructura

```
training-tracker/
├── app/                        ← Symfony 7.4 + PHP 8.3 (monolito modular)
│   ├── src/
│   │   ├── Shared/             ← kernel, controllers base, eventos globales
│   │   ├── Training/           ← mesociclos, sesiones, ejercicios, logs
│   │   ├── Nutrition/          ← planes, comidas, adherencia
│   │   ├── Wearables/          ← HealthMetric canónico, Activity, Sleep
│   │   ├── Analytics/          ← progresión, volumen, StatGuard
│   │   └── Ingestion/          ← parser PDF de mesociclos y dieta
│   ├── config/packages/        ← Doctrine, Messenger, AssetMapper
│   ├── templates/              ← Twig
│   ├── assets/                 ← JS + CSS vía AssetMapper
│   ├── public/                 ← manifest.webmanifest + sw.js + index.php
│   └── tests/                  ← Pest (Unit, Feature) + Playwright (e2e)
├── sidecar-garmin/             ← FastAPI + python-garminconnect
│   ├── src/sidecar/
│   └── tests/
├── docker/                     ← Dockerfiles y nginx.conf
├── docker-compose.yml
├── Makefile                    ← `make help` para ver targets
├── openspec/                   ← SDD: cambios, specs, design
│   ├── config.yaml
│   ├── specs/                  ← source of truth
│   └── changes/                ← propuestas en curso, archive de cerradas
├── .atl/skill-registry.md      ← compact rules para sub-agentes SDD
└── .github/workflows/ci.yml    ← CI jobs PHP + Python
```

## Módulos y boundaries

- Cada módulo es un bounded context con namespace `App\{Module}\`.
- Tablas prefijadas por módulo: `training_*`, `nutrition_*`, `wearables_*`, etc.
- **Prohibido**: `ORM\JoinColumn` cruzando módulos (excepto hacia `Shared`). Comunicación vía Symfony Messenger events.
- Las entidades de un módulo nunca importan entidades de otro módulo. Si necesitás el dato, pasá snapshots de valor (int, string) o publicá/consumí un event.

## Comandos útiles

```bash
make up              # docker compose up -d --wait
make down            # docker compose down
make test            # Pest + pytest
make test-php        # Pest --testdox
make test-py         # pytest
make e2e             # Playwright local (requiere Node + npm install + playwright install)
make migrate         # Doctrine migrations
make shell           # bash en el container app
make shell-sidecar   # bash en el container sidecar
make logs            # tail de todos los services
make clean           # down -v (DESTRUYE volúmenes)
```

## SDD — trabajo en curso

Cambios planificados viven en `openspec/changes/{change-name}/`:
- `proposal.md` — intent, scope, rollback, success criteria
- `specs/{capability}/spec.md` — requisitos con Given/When/Then + RFC 2119
- `design.md` — decisiones arquitectónicas
- `tasks.md` — checklist ejecutable

Cambios completados se mueven a `openspec/changes/archive/YYYY-MM-DD-{change-name}/`.

## CI

GitHub Actions `.github/workflows/ci.yml` corre en cada push y PR:

- `php-tests`: Pest + PHPStan nivel 6.
- `python-tests`: ruff + mypy strict + pytest.

E2E Playwright corre local en Fase 0 y se añade a CI en Fase 1.

## Estado

Fase 0 — scaffold listo. Fases siguientes están descritas en el plan interno (v2 rewrite: logueo → nutrición → Garmin → analytics → parser PDF). Ver `openspec/changes/` para lo que hay en curso.
