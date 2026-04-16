# Exploration: fase-1a-modelo-training

Design doc §4.1 ya define entidades y contratos de `repsScheme` jsonb. No se re-investiga. Esta exploración resuelve las 6 decisiones de implementación Doctrine/PHP/Symfony.

## Current State

Módulo `src/Training/` vacío (solo `Entity/.gitkeep`). Doctrine configurado con mapping `Training → src/Training/Entity` con prefix `App\Training\Entity` (auto_mapping: false). Postgres 16 up. Migraciones dir vacío salvo `.gitignore`.

## Gap 1 — Mapping de jsonb (`repsScheme`, `rirScheme`, `weekProgression`)

### Approaches

1. **`Types::JSON` + Value Objects reconstruidos en getter**
   - Columna Doctrine `type: 'json'` (PostgreSQL la persiste como `jsonb` vía el driver). Getter hidrata a VO según `type`.
   - Pros: zero custom Doctrine type; VO en `Training/Domain/ValueObject/RepsScheme/` da type safety en servicios; fácil de validar con Symfony Validator.
   - Cons: el `PlannedSetGroup` expone `getRepsSchemeRaw(): array` + `getRepsScheme(): RepsScheme` (dos accesos, uno a la raw + uno al VO). Convivible.
   - Effort: Low.

2. **Custom Doctrine Type (`RepsSchemeType`)**
   - Tipo custom que hidrata jsonb directamente a VO.
   - Pros: un solo accesor tipado.
   - Cons: registrar tipo en `doctrine.yaml`, mayor acoplamiento ORM↔dominio, mock/test más complejo.
   - Effort: Medium.

3. **`#[ORM\Embedded]` con columnas aplanadas**
   - Descartado: embeddables son flat — no soportan variantes polimórficas.

**Recomendación**: Approach 1. Column jsonb, VO factory en dominio.

### Contratos jsonb (Symfony Validator)

```php
// src/Training/Domain/ValueObject/RepsScheme/
abstract readonly class RepsScheme {
    public static function fromArray(SetGroupType $type, array $raw): self { /* factory */ }
}
final readonly class StraightScheme extends RepsScheme { public function __construct(public string $reps) {} }
final readonly class AmrapScheme extends RepsScheme { public function __construct(public int $target, public int $min) {} }
// DescendingScheme { public array<int> $drops }
// ClusterScheme { public int $subSets, public int $reps, public int $intraRestSeconds }
// RestPauseScheme { public int $initial, public array $microSets, public int $intraRestSeconds }
// PapScheme { public int $heavySingle, public int $workSets, public string $workReps }
// SupersetScheme { public string $pairedWith }
// PoliquinTrisetScheme { public int $a, public int $b, public int $c }
// ScdScheme { public array<int> $descending, public array<int> $holdSeconds }
```

## Gap 2 — Enums PHP 8.3

Catálogo cerrado (todos `string`-backed para serialización legible en jsonb + stability migracional):

| Enum | Valores | Uso |
|------|---------|-----|
| `MesocycleGoal` | HYROX, FAT_LOSS_RUNNING, HYBRID, STRENGTH, HYPERTROPHY, MAINTENANCE | `Mesocycle.goal` |
| `SetGroupType` | STRAIGHT, AMRAP, DESCENDING, CLUSTER, REST_PAUSE, PAP, SUPERSET, POLIQUIN_TRISET, PARTIAL_PLUS_FULL, SCD | `PlannedSetGroup.type` |
| `SetModifier` | NONE, MANTEN_PESO, REDUCE_PESO | `PlannedSetGroup.modifier` |
| `MuscleGroup` | CHEST, BACK, SHOULDER_ANT, SHOULDER_LAT, SHOULDER_POST, BICEPS, TRICEPS, FOREARMS, QUADS, HAMSTRINGS, GLUTES, CALVES, CORE | `ExerciseCatalog.muscleGroups[]` |
| `Equipment` | BARBELL, DUMBBELL, MACHINE_PLATES, MACHINE_SELECTORIZED, CABLE, KETTLEBELL, BODYWEIGHT, BANDS, SMITH, OTHER | `ExerciseCatalog.equipment` |

**`muscleGroups[]` como colección**: Doctrine no soporta `array<MuscleGroup>` nativo. Storage: `type: 'json'` guardando `string[]`. Getter/setter hidrata a/desde `MuscleGroup`. Validator constraint `Count(min=1)` + `All(new Enum(MuscleGroup::class))`.

Mapping Doctrine para enum escalar: `#[ORM\Column(type: Types::STRING, enumType: MesocycleGoal::class)]` — nativo en Doctrine ORM 3.

## Gap 3 — Estrategia de migraciones

### Organización

Una sola carpeta `app/migrations/`. Nombre `VersionYYYYMMDDHHMMSS_<modulo>_<accion>.php`. Orden cronológico por timestamp = orden de aplicación.

### Split propuesto para Fase 1a

| Version | Tabla(s) | Deps |
|---------|----------|------|
| V1 | `training_exercise_catalog` | — |
| V2 | `training_mesocycle` | — |
| V3 | `training_session_template` | V2 |
| V4 | `training_planned_exercise` | V1 + V3 |
| V5 | `training_planned_set_group` | V4 (self-FK `linked_set_group_id`) |
| V6 | `training_session` + `training_set_log` + índices críticos del §4.4 | V3 + V5 |

FKs `Session.sessionTemplateId → SessionTemplate.id`, `SetLog.sessionId → Session.id`, `SetLog.plannedSetGroupId → PlannedSetGroup.id` son **intra-módulo** → permitidas. No violan boundaries.

### Trade-off vs monolítica
Una migración única sería más rápida de escribir pero un split de 6 es más legible, revisable en PR y permite rollback granular si algo rompe en prod (hoy es single-user, pero la práctica es barata).

**Recomendación**: 6 migraciones.

## Gap 4 — Polimorfismo de `PlannedSetGroup`

### Approaches

1. **Tabla única + discriminator como enum column + jsonb para variante**
   - Una entidad PHP `PlannedSetGroup`. Columna `type SetGroupType`. Columnas `reps_scheme jsonb`, `rir_scheme jsonb`. VO factory hidrata según `type`.
   - Pros: 1 entidad, 1 migración, queries directas, encaja con el diseño de `repsScheme` jsonb ya decidido.
   - Cons: type safety via runtime factory, no via herencia. Validación de shape vive en servicio.
   - Effort: Low.

2. **Single Table Inheritance (Doctrine STI)**
   - `#[InheritanceType('SINGLE_TABLE')]` + 10 subclases (`StraightSetGroup`, `AmrapSetGroup`, …).
   - Pros: herencia explícita, `instanceof` funciona.
   - Cons: 10 clases, cambios al dominio requieren migración de subclase, Doctrine STI no se lleva bien con enums como discriminator (string column separado del enum goal).
   - Effort: High.

3. **Class Table Inheritance**: descartado — JOIN por cada query es costoso y el dominio no justifica.

**Recomendación**: Approach 1. Consistente con §4.1, menor fricción, VO factory da la rigurosidad necesaria en application layer.

## Gap 5 — Admin / carga del Meso 17

### Approaches

1. **Import JSON vía comando Symfony + vista read-only Twig**
   - `bin/console app:training:import-mesocycle <file.json>` lee JSON (mismo schema que producirá el parser PDF de Fase 5) y crea `Mesocycle` completo en una transacción.
   - Una ruta admin `/admin/mesocycles` lista los meso existentes con drill-down a `SessionTemplate`.
   - Pros: zero fricción para cargar Meso 17 (editar un JSON es más rápido que 300 forms), TDD-amigable, re-usable en tests y Fase 5.
   - Cons: requiere escribir un JSON a mano (o copiar del PDF). Para Fase 1a es aceptable — el parser real llega en Fase 5.
   - Effort: Medium.

2. **EasyAdmin 4 completo**
   - Dashboard con CRUDs anidados de todas las entidades Training.
   - Pros: edición granular bonita.
   - Cons: jsonb requiere custom form field (o `CodeEditorField`, que es un `textarea` con highlight); flujo jerárquico son ~300 formularios para 1 meso. Exagerado para Fase 1a.
   - Effort: High.

3. **Plain Twig forms**
   - Controllers + Symfony Forms.
   - Pros: flexible.
   - Cons: labor-intensive, sin ganar sobre (1).
   - Effort: High.

**Recomendación**: Approach 1 — JSON import + vista read-only. EasyAdmin se considera en Fase 2+ si se necesita editar post-import, no antes.

## Gap 6 — Seed de `ExerciseCatalog`

### Approaches

1. **JSON seed + comando idempotente**
   - `config/fixtures/exercises.json` con ~60 ejercicios iniciales (copiados del PDF). Comando `app:training:seed-exercises` hace upsert por `slug`.
   - Pros: mismo flujo en dev/test/prod. Idempotente. Fácil añadir ejercicios.
   - Cons: mantener el JSON a mano (one-shot setup, después se añaden por import de meso).
   - Effort: Low.

2. **DoctrineFixturesBundle con fixture PHP**
   - `src/Training/DataFixtures/ExerciseCatalogFixture.php`.
   - Pros: idiomatic Symfony. Integra con `doctrine:fixtures:load`.
   - Cons: añade dependencia dev. Menos portable que JSON.
   - Effort: Low.

3. **SQL seed en migración**
   - Descartado: acopla schema migration con data seed. Si el catálogo crece, la migración crece.

**Recomendación**: Approach 1. El JSON también servirá como contrato para el parser PDF en Fase 5 (mismo schema → import de ejercicios desconocidos).

## Ready for Proposal

Yes. Las 6 decisiones están tomadas con recomendación clara. `sdd-propose` puede emitir un proposal con scope acotado: modelo + migraciones + 2 comandos (`import-mesocycle`, `seed-exercises`) + vista read-only de meso, todo con TDD (strict activo desde Fase 1).

## Risks

- **Validación de `repsScheme` shape vs `type`**: un JSON importado con `type=AMRAP` pero `reps_scheme = {"reps": "6-8"}` silenciosamente se guarda como STRAIGHT-like. Mitigación: Symfony Validator en `PlannedSetGroup` factory method; rechazar la transacción entera si falla.
- **Seeds del catálogo divergen del JSON del parser PDF**: si el slug cambia entre JSON seed y parser, se duplican ejercicios. Mitigación: `slug` canónico con normalizer (lowercase, kebab-case, sin acentos).
- **Ejecución del import antes de las migraciones**: `app:training:import-mesocycle` requiere schema aplicado. Documentar en README + defensive check en el comando (query a information_schema).
- **Performance del `SetLog` en mesociclos largos**: el índice compuesto `(planned_set_group_id, session_id)` del §4.4 es crítico. Olvidarlo degrada queries de "última ejecución" a full scan. Incluir en V6 sin excepción.
