# Training Model Specification

## Purpose

Define persistencia, invariantes y operaciones de gestión del dominio Training: mesociclos, sesiones, ejercicios y logs. Define QUÉ debe almacenarse, validarse e importarse; el cómo (Doctrine attributes, VO factories) vive en `design.md`.

## Requirements

### Requirement: Entidades y boundaries del módulo Training

El módulo `App\Training\Entity` MUST exponer 7 entidades: `Mesocycle`, `SessionTemplate`, `ExerciseCatalog`, `PlannedExercise`, `PlannedSetGroup`, `Session`, `SetLog`. Cada tabla MUST prefijarse `training_*`. Ninguna entidad MUST declarar FK hacia un namespace raíz distinto a `App\Shared` o `App\Training`.

#### Scenario: Tablas prefijadas y sin FK cross-module

- GIVEN migraciones aplicadas
- WHEN se inspecciona `information_schema.tables` y `pg_constraint`
- THEN existen 7 tablas con prefijo `training_` y todas las FKs apuntan a otras tablas `training_*` o `shared_*` (no a `nutrition_*`, `wearables_*`, etc.)

### Requirement: Enums backed del dominio

El sistema MUST definir 5 enums PHP 8.3 string-backed en `App\Training\Enum`: `MesocycleGoal`, `SetGroupType`, `SetModifier`, `MuscleGroup`, `Equipment`. Los valores MUST ser estables (cambiarlos requiere migración).

#### Scenario: Enum mapeado en columna

- GIVEN un `Mesocycle` persistido con `goal = MesocycleGoal::HYROX`
- WHEN se lee la fila cruda de postgres
- THEN la columna `goal` contiene el literal `"HYROX"`

### Requirement: Value Object `RepsScheme` valida shape vs `SetGroupType`

La factory `RepsScheme::fromArray(SetGroupType $type, array $raw)` MUST construir la variante correcta (9 variantes según `§4.1`) o lanzar `InvalidSchemeException`. La validación MUST ejecutarse antes de persistir un `PlannedSetGroup`.

#### Scenario: Shape válido produce VO correcto

- GIVEN `type = SetGroupType::AMRAP` y `raw = {"target": 8, "min": 7}`
- WHEN se invoca `RepsScheme::fromArray(type, raw)`
- THEN devuelve instancia de `AmrapScheme` con `target=8`, `min=7`

#### Scenario: Shape incongruente con type es rechazado

- GIVEN `type = SetGroupType::AMRAP` y `raw = {"reps": "6-8"}` (shape de STRAIGHT)
- WHEN se invoca `RepsScheme::fromArray(type, raw)` o se persiste el `PlannedSetGroup`
- THEN se lanza `InvalidSchemeException` y no se escribe en DB

### Requirement: Migraciones Doctrine aplicables y validables

Las migraciones MUST aplicarse en orden cronológico sin errores y `doctrine:schema:validate` MUST reportar OK tras ejecutarlas.

#### Scenario: Migrate + validate en DB limpia

- GIVEN DB `training_tracker` sin tablas Training
- WHEN se ejecuta `doctrine:migrations:migrate -n` seguido de `doctrine:schema:validate`
- THEN exit code 0 en ambos y el mapping está sincronizado con el schema

### Requirement: Índices críticos sobre `SetLog`

La tabla `training_set_log` MUST definir: índice compuesto `(planned_set_group_id, session_id)`, índice `(session_id, logged_at_client)`, único `(client_generated_id)`, índice `(session_id)`.

#### Scenario: Índices presentes tras migración

- GIVEN migraciones aplicadas
- WHEN se consulta `pg_indexes WHERE tablename = 'training_set_log'`
- THEN existen los 4 índices listados

### Requirement: Comando `app:training:seed-exercises` idempotente

El comando MUST cargar `config/fixtures/exercises.json` y hacer upsert por `slug` canónico (lowercase, kebab-case, sin acentos). Ejecuciones repetidas MUST NOT crear duplicados.

#### Scenario: Primera y segunda ejecución

- GIVEN `exercises.json` con 60 entradas y `training_exercise_catalog` vacío
- WHEN se ejecuta el comando dos veces consecutivas
- THEN la primera inserta 60 filas, la segunda inserta 0 y termina con exit code 0

### Requirement: Comando `app:training:import-mesocycle` transaccional

El comando MUST aceptar un path JSON, validar el schema completo contra el contrato de VOs y persistir `Mesocycle + SessionTemplate + PlannedExercise + PlannedSetGroup` en una única transacción. Cualquier fallo MUST revertir toda la transacción.

#### Scenario: Import válido

- GIVEN JSON bien formado describiendo Meso 17 completo
- WHEN se ejecuta el comando
- THEN exit code 0 y la DB contiene el mesociclo con toda su jerarquía

#### Scenario: Fallo parcial revierte todo

- GIVEN JSON con un `PlannedSetGroup` que tiene shape incongruente con su type
- WHEN se ejecuta el comando
- THEN exit code != 0, `InvalidSchemeException` en stderr y `training_mesocycle` NO contiene registros del import

### Requirement: Ruta read-only `/admin/mesocycles`

El controller MUST responder 200 con listado de mesociclos y permitir drill-down `/admin/mesocycles/{id}` mostrando jerarquía de sesiones, ejercicios planificados y set groups. No MUST exponer endpoints de mutación.

#### Scenario: Listado tras import

- GIVEN Meso 17 importado
- WHEN se hace GET `/admin/mesocycles`
- THEN response 200 y el body contiene "Meso 17" con link a su detalle

#### Scenario: Detalle drill-down

- GIVEN Meso 17 con 4 `SessionTemplate`s
- WHEN se hace GET `/admin/mesocycles/{id}`
- THEN response 200 y el body lista las 4 sesiones, cada una con sus ejercicios y set groups

### Requirement: Tests con TDD Cycle Evidence

Todos los tests de `Training/` MUST documentarse en `apply-progress` con tabla RED → GREEN → REFACTOR por task (strict_tdd activo). Coverage de `Training/Domain` y `Training/Command` SHOULD ser ≥ 90%.

#### Scenario: sdd-verify exige TDD Evidence

- GIVEN apply-progress sin tabla TDD Evidence
- WHEN `sdd-verify` corre
- THEN reporta FAIL con CRITICAL "TDD Evidence table missing"
