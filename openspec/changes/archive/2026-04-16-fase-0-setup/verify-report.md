# Verification Report — fase-0-setup

**Mode**: Standard (strict_tdd flipped to `true` AFTER this fase landed; applies from Fase 1 onward. Fase 0 was scaffold-only, TDD Cycle Evidence not applicable).

---

## Completeness

| Metric | Value |
|--------|-------|
| Tasks total | 33 |
| Tasks complete | 32 |
| Tasks incomplete | 1 (task 7.7 = this verify + archive, in progress) |

No critical task pending.

---

## Build & Tests Execution

**Build / static analysis**:
- PHPStan nivel 6 sobre `src`: ✅ `[OK] No errors`
- ruff sobre `src tests`: ✅ `All checks passed!`
- mypy strict sobre `src`: ✅ `Success: no issues found in 2 source files`
- Doctrine `schema:validate --skip-sync`: ✅ mappings correct

**Tests**:
- Pest (PHP): ✅ `OK (1 test, 1 assertion)` — `tests/Unit/SmokeTest.php > it arithmetic sanity`
- pytest (Python): ✅ `1 passed` — `tests/test_health.py::test_health_returns_ok`
- Playwright E2E: ➖ no ejecutado en esta verificación (local-only en Fase 0, pospuesto al CI de F1)

**Coverage**: ➖ Not configured in Fase 0. Threshold se añadirá cuando haya lógica de negocio que cubrir.

---

## Runtime Evidence

```
docker compose ps:
  app        healthy
  nginx      running
  postgres   healthy
  redis      running
  sidecar    healthy

curl localhost:8000/                    → 200 text/html
curl localhost:8000/manifest.webmanifest → 200 application/manifest+json
curl localhost:8000/sw.js                → 200 application/javascript
curl localhost:8001/health               → 200 {"status":"ok","version":"0.1.0"}

manifest JSON keys present: name, short_name, start_url(="/"), display(="standalone"), theme_color, icons
```

---

## Spec Compliance Matrix

| Requirement | Scenario | Test / Evidence | Result |
|-------------|----------|-----------------|--------|
| REQ-01 Monorepo layout | Raíz expone workspaces y ficheros de gobernanza | `ls` runtime: app/, sidecar-garmin/, docker/, docker-compose.yml, Makefile, README.md, .env.example, .gitignore, CLAUDE.md | ✅ COMPLIANT |
| REQ-02 Módulos con boundaries | Namespaces aislados por módulo | `autoload_psr4.php` lista los 6 App\{Module}\ → src/{Module}/ | ✅ COMPLIANT |
| REQ-02 Módulos con boundaries | No cross-module imports | `grep -rE '^use App\\(Training\|Nutrition\|Wearables\|Analytics\|Ingestion)\\' app/src/` → No matches | ✅ COMPLIANT |
| REQ-03 Doctrine mapping por módulo | schema:validate sin errores con módulos vacíos | `doctrine:schema:validate --skip-sync` → `[OK]` | ✅ COMPLIANT |
| REQ-04 Docker stack arranca | Servicios healthy tras make up | `docker compose ps` → app/postgres/sidecar healthy, nginx/redis running | ✅ COMPLIANT |
| REQ-04 Docker stack arranca | App responde 200 en home | `curl localhost:8000/` → 200 text/html | ✅ COMPLIANT |
| REQ-04 Docker stack arranca | Sidecar responde /health | `curl localhost:8001/health` → 200 `{"status":"ok","version":"0.1.0"}` | ✅ COMPLIANT |
| REQ-05 PWA manifest y SW servidos | Manifest disponible | `curl /manifest.webmanifest` → 200 application/manifest+json + JSON contiene name/short_name/start_url/display=standalone | ✅ COMPLIANT |
| REQ-06 Harness multi-runtime | Pest smoke pasa | `vendor/bin/pest --testdox` → OK 1 test | ✅ COMPLIANT |
| REQ-06 Harness multi-runtime | pytest smoke pasa | `pytest` → 1 passed | ✅ COMPLIANT |
| REQ-06 Harness multi-runtime | Playwright smoke pasa local | Config + spec presentes (`app/playwright.config.ts`, `tests/e2e/smoke.spec.ts`); ejecución requiere `npm install && npx playwright install`. No ejecutado en esta verificación. | ⚠️ PARTIAL — no ejecutado, solo structural evidence |
| REQ-07 CI GitHub Actions verde en primer push | CI verde al push inicial | `.github/workflows/ci.yml` existe con jobs php-tests + python-tests paralelos; ejecución en GitHub Actions pendiente del push del usuario | ⚠️ PARTIAL — no ejecutado, solo structural evidence |

**Compliance summary**: 10/12 scenarios ✅ COMPLIANT, 2/12 ⚠️ PARTIAL (E2E local + CI remoto — ambos requieren acción externa que no puede ejecutarse en este container host).

---

## Correctness (Static)

| Requirement | Status | Notes |
|------------|--------|-------|
| Monorepo layout | ✅ Implemented | 10 entradas raíz según spec |
| Módulos con boundaries | ✅ Implemented | 6 módulos con PSR-4, sin imports cruzados |
| Doctrine mapping por módulo | ✅ Implemented | auto_mapping: false, 6 mappings explícitos |
| Docker stack arranca | ✅ Implemented | 5 servicios con healthchecks |
| PWA manifest y SW | ✅ Implemented | Manifest completo + SW con BackgroundSync queue |
| Harness multi-runtime | ✅ Implemented | Pest, pytest, Playwright configurados |
| CI GitHub Actions | ✅ Implemented | ci.yml con 2 jobs paralelos + caches |

---

## Coherence (Design)

| Decision | Followed? | Notes |
|----------|-----------|-------|
| AssetMapper (no Webpack) | ✅ Yes | importmap.php + asset-mapper package |
| Workbox solo (no pwa-bundle) | ✅ Yes | SW usa importScripts a Google CDN |
| Mono-DB + 6 mappings + prefijo tabla | ✅ Yes | auto_mapping: false confirmado |
| FKs cross-module prohibidas | ✅ Yes | 0 imports cruzados (no hay entidades aún; convención queda lista) |
| php-fpm + nginx en Docker | ✅ Yes | Confirmado decisión de design |
| Symfony 7.4 LTS | ✅ Yes | composer.json require `7.4.*` |

---

## Issues Found

### CRITICAL
None. Todos los requirements ejecutables en este entorno pasan.

### WARNING
- **Playwright E2E no ejecutado**: el spec scenario "Playwright smoke pasa local" requiere `npm install` en `app/` y `npx playwright install`. Ambos se difieren al usuario (no requieren docker). Al ser Fase 0 con UI mínima, la evidencia structural (config + spec existen) es suficiente para cerrar la fase; debería ejecutarse al menos una vez antes de pasar a Fase 1.
- **CI no ejecutado**: el scenario "CI verde al push inicial" requiere push a GitHub. Todo local está verde, incluidos los comandos que el workflow ejecutará. Se cerrará tras el primer push con CI green.

### SUGGESTION
- **Iconos del manifest**: `manifest.webmanifest` referencia `/icons/icon-192.png` y `/icons/icon-512.png` que no existen aún. Browsers logueará 404 al instalar la PWA. Añadir placeholders o ajustar manifest antes de Fase 1.
- **phpstan nivel 6** (no max): cuando haya código real, considerar subir a level 8 o max para aprovechar PHP 8.3 typing.
- **PWA lang=es**: el manifest declara `lang: "es"` pero la UI aún es mínima. Confirmar que Fase 1 mantiene consistencia.

---

## Verdict

**PASS WITH WARNINGS** — Fase 0 scaffold cumple el spec `project-foundation` en los 10 escenarios que pueden ejecutarse automáticamente contra el stack activo. 2 escenarios (Playwright E2E + CI remoto) quedan ⚠️ PARTIAL porque requieren acciones externas del usuario (Node install / git push); la evidencia structural (ficheros de config + test specs presentes) es completa.

Ready for `sdd-archive` una vez el usuario confirme que acepta cerrar con las 2 warnings pendientes de ejecución externa. Alternativamente, el usuario puede ejecutar E2E local + push a GitHub para convertir las 2 PARTIAL en COMPLIANT antes de archivar.
