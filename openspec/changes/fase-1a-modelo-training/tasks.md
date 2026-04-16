# Tasks: Fase 1a — Modelo Training

TDD estricto. Cada task con lógica es RED (test fail) → GREEN (code) → REFACTOR. Documentar en `apply-progress` con TDD Cycle Evidence obligatoria.

## Phase 1: Enums y Value Objects

- [x] 1.1 5 enums backed en `src/Training/Enum/`. 13 tests / 75 assertions ✅
- [x] 1.2 `InvalidSchemeException` + abstract `RepsScheme` + `StraightScheme`. 4 tests ✅
- [x] 1.3 `AmrapScheme`, `DescendingScheme`, `ClusterScheme`. 14 tests ✅
- [x] 1.4 `RestPauseScheme`, `PapScheme`, `SupersetScheme`, `PoliquinTrisetScheme`, `ScdScheme` + `PartialPlusFullScheme` (añadido porque enum tiene 10 types, factory los necesita todos). 20 tests ✅
- [x] 1.5 `RepsSchemeFactory::fromArray(type, raw)` con `match`, validación por clave+tipo, envuelve `InvalidArgumentException` como `InvalidSchemeException`. 14 tests incluyendo los 2 scenarios del spec ✅
- [x] 1.6 `RirScheme` (perSeries tokens F|dígitos) + `WeekProgression` (map week → data). 9 tests ✅
- [x] 1.7 `ExerciseSlugifier` con `AsciiSlugger('es')` + lower + trim. 7 tests con diacríticos, eñe, punctuación, idempotencia ✅

## Phase 2: Entidades + Migraciones

- [ ] 2.1 RGR: `ExerciseCatalog` entity con `muscleGroups[]` jsonb hidratado + `equipment` enum + slug único.
- [ ] 2.2 Crear `Version20260417120000_training_create_exercise_catalog.php` con tabla + índice único slug.
- [ ] 2.3 RGR: `Mesocycle` entity con `goal` enum, `weeks` int, fechas, `structureNote` text nullable.
- [ ] 2.4 Crear `Version20260417120100_training_create_mesocycle.php`.
- [ ] 2.5 RGR: `SessionTemplate` entity con FK a `Mesocycle`, `orderInWeek`, `label`, `hasCardioBlock`.
- [ ] 2.6 Crear `Version20260417120200_training_create_session_template.php`.
- [ ] 2.7 RGR: `PlannedExercise` con FKs a `SessionTemplate` + `ExerciseCatalog`, `letter`, `orderInSession`, notas.
- [ ] 2.8 Crear `Version20260417120300_training_create_planned_exercise.php`.
- [ ] 2.9 RGR: `PlannedSetGroup` entity con enum `type`, jsonb `reps_scheme`/`rir_scheme`, self-FK `linked_set_group_id`.
- [ ] 2.10 Crear `Version20260417120400_training_create_planned_set_group.php`.
- [ ] 2.11 RGR: `Session` entity (FK SessionTemplate+Mesocycle, snapshot readiness) + `SetLog` con `clientGeneratedId` UUID único.
- [ ] 2.12 Crear `Version20260417120500_training_create_session_and_set_log.php` con los 4 índices críticos del §4.4. Cubre spec "Índices presentes".

## Phase 3: Repositorios

- [ ] 3.1 RGR: `MesocycleRepository::findBySlug`, `ExerciseCatalogRepository::findBySlug`, `SetLogRepository::findLastForPlannedGroup(id)`, `SessionRepository::findByDate`.

## Phase 4: Comando seed-exercises

- [ ] 4.1 Crear `config/fixtures/exercises.json` con ~60 ejercicios del PDF (name, slug, muscleGroups, equipment, videoUrl opcional).
- [ ] 4.2 RGR: `SeedExercisesCommand` idempotente (upsert por slug). Cubre spec "Primera y segunda ejecución".

## Phase 5: Import command + service

- [ ] 5.1 RGR: `MesocycleImporter::validate(array $json)` recorre la jerarquía y ejecuta `RepsSchemeFactory::fromArray` sobre cada set group (falla temprano).
- [ ] 5.2 RGR: `MesocycleImporter::persist(array $json)` dentro de `EntityManager::wrapInTransaction`. Cubre spec "Import válido" y "Fallo parcial revierte todo".
- [ ] 5.3 RGR: `ImportMesocycleCommand` CLI que orquesta validate + persist, exit codes.
- [ ] 5.4 Crear `config/fixtures/meso-17.json` (o `meso-17.test.json` si el real no está listo) con al menos 1 week + 2 sessions + 4 exercises para tests.

## Phase 6: Admin read-only

- [ ] 6.1 RGR: `AdminMesocycleController::index` → `/admin/mesocycles` lista. Cubre spec "Listado tras import".
- [ ] 6.2 RGR: `AdminMesocycleController::show` → `/admin/mesocycles/{id}` con drill-down completo. Cubre spec "Detalle drill-down".
- [ ] 6.3 Crear `templates/admin/mesocycle/{index,show}.html.twig` minimal (tabla simple, sin CSS bonito — Fase 1b trae layout).

## Phase 7: Integration tests end-to-end

- [ ] 7.1 Test integration Pest: `doctrine:migrations:migrate` + `doctrine:schema:validate --skip-sync` exit 0. Cubre spec "Migrate + validate".
- [ ] 7.2 Test integration Pest: ejecuta `seed-exercises` dos veces, verifica 60 + 0 inserts.
- [ ] 7.3 Test functional WebTestCase: import meso-17.test.json + GET `/admin/mesocycles` → 200 con el nombre visible.
- [ ] 7.4 Test: grep cross-module imports sobre `src/Training/` → 0 matches (spec boundaries).

## Phase 8: Verificación

- [ ] 8.1 Correr Pest full suite (+ coverage si disponible). Verificar TDD Evidence en apply-progress.
- [ ] 8.2 Correr PHPStan nivel 6 sobre `src/Training/` → 0 errores.
- [ ] 8.3 `sdd-verify` contra `training-model` spec; si pasa, `sdd-archive`.
