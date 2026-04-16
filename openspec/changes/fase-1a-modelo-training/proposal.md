# Proposal: Fase 1a — Modelo Training

## Intent

Aterrizar el modelo de dominio Training (mesociclos, sesiones, ejercicios, logs) con persistencia real en Postgres. Sin UI de logueo aún. Al cerrar esta fase podés cargar el Meso 17 en la DB vía comando y consultarlo por una vista read-only; la UX de logueo se construye encima en Fase 1b.

## Scope

### In Scope
- 7 entidades Training: `Mesocycle`, `SessionTemplate`, `ExerciseCatalog`, `PlannedExercise`, `PlannedSetGroup`, `Session`, `SetLog`.
- 5 enums PHP 8.3 backed: `MesocycleGoal`, `SetGroupType`, `SetModifier`, `MuscleGroup`, `Equipment`.
- Value Objects `RepsScheme` (9 variantes) + `RirScheme` + `WeekProgression` en `Training/Domain/ValueObject/`.
- 6 migraciones Doctrine ordenadas con prefijo `training_*`.
- Índices críticos del design doc §4.4 (`idx_setlog_planned_session`, `uq_setlog_client_gen`, etc.).
- Comando `app:training:seed-exercises` (idempotente, lee `config/fixtures/exercises.json`).
- Comando `app:training:import-mesocycle <file.json>` (carga meso entero en transacción, mismo schema que usará el parser PDF de Fase 5).
- JSON fixture con ~60 ejercicios iniciales del PDF de Alex.
- Ruta `/admin/mesocycles` (Twig read-only): lista mesociclos, drill-down a `SessionTemplate` → `PlannedExercise` → `PlannedSetGroup`.
- Tests Pest TDD: VO factory, enums, entity invariants, comandos, controller read-only. Integration tests con DB real.

### Out of Scope
- UI de logueo offline (Fase 1b).
- PWA sync, IndexedDB, steppers (Fase 1b).
- EasyAdmin / edición de meso post-import (se evaluará en Fase 2+).
- Cardio `weekProgression` avanzado (solo estructura básica; lógica de progresión en Fase 1b/Analytics).
- Módulos Nutrition, Wearables, Analytics, Ingestion (fases posteriores).

## Capabilities

### New Capabilities
- `training-model`: persistencia, invariantes y comandos de gestión del dominio Training (mesociclos, sesiones, ejercicios, set logs). Define qué se puede almacenar, validar e importar.

### Modified Capabilities
- `project-foundation`: None (no cambia comportamiento; este change solo añade tablas dentro del módulo Training respetando los boundaries ya especificados).

## Approach

TDD estricto (strict_tdd activo). Cada entidad, VO y comando pasa por RED-GREEN-REFACTOR:

1. Escribir test Pest que describe invariante/comportamiento (falla).
2. Implementar lo mínimo para pasar.
3. Refactor.

Orden: enums → VOs (factory + validación) → entidades + migraciones → comandos → vista read-only. Una sola entidad por sesión. CI debe quedar verde en cada commit.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/src/Training/Entity/*` | New | 7 entidades con atributos Doctrine 3 |
| `app/src/Training/Enum/*` | New | 5 enums backed |
| `app/src/Training/Domain/ValueObject/*` | New | VOs jsonb (RepsScheme + variantes) |
| `app/src/Training/Command/*` | New | 2 comandos console (seed, import) |
| `app/src/Training/Controller/AdminController.php` | New | Ruta `/admin/mesocycles` read-only |
| `app/src/Training/Repository/*` | New | Repositorios Doctrine |
| `app/migrations/Version*.php` | New | 6 migraciones |
| `app/templates/admin/*` | New | Vistas Twig read-only |
| `app/config/fixtures/exercises.json` | New | Seed de catálogo |
| `app/tests/Unit/Training/*`, `app/tests/Integration/Training/*` | New | Tests Pest |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Shape de `repsScheme` incongruente con `type` | Med | Symfony Validator en factory; rechaza la transacción entera |
| Slug divergente entre seed y parser PDF | Med | Slug canónico normalizado (slugify) con tests |
| Olvidar índices críticos de `SetLog` | Low | Checklist en `sdd-verify` contra §4.4 del design doc |
| Import transaccional parcial | Low | `EntityManager::wrapInTransaction`; rollback total ante cualquier fallo |

## Rollback Plan

Change aditivo. Rollback = `git revert <merge commit>` + `php bin/console doctrine:migrations:migrate prev` para cada versión creada. Ninguna migración modifica tablas existentes (solo crea nuevas). Datos de Meso 17 importados se pierden, pero el JSON fuente queda en disco para re-importar.

## Dependencies

- Fase 0 archivada ✅ (stack Docker, Symfony 7.4, Doctrine configurado, harness Pest).
- Postgres 16 up (healthcheck OK).
- PDF del Meso 17 como fuente manual del JSON de import.

## Success Criteria

- [ ] `bin/console doctrine:migrations:migrate` aplica las 6 versions sin errores.
- [ ] `bin/console doctrine:schema:validate` → OK tras migraciones.
- [ ] `bin/console app:training:seed-exercises` crea ~60 ejercicios idempotentemente (segunda ejecución = 0 inserts).
- [ ] `bin/console app:training:import-mesocycle config/fixtures/meso-17.json` crea `Mesocycle` + jerarquía completa en una transacción.
- [ ] `/admin/mesocycles` devuelve 200 y lista el Meso 17 importado.
- [ ] Tests Pest (Unit + Integration) todos en verde, con TDD Cycle Evidence documentado en apply-progress.
- [ ] Validator rechaza JSON con `type=AMRAP` pero `reps_scheme = {"reps":"6-8"}`.
- [ ] 0 imports cross-module (grep en `sdd-verify`).
- [ ] CI verde.
