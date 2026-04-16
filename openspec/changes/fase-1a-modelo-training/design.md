# Design: Fase 1a — Modelo Training

## Technical Approach

Módulo `App\Training\` con Doctrine ORM 3 + PHP 8.3. Columnas jsonb para schemes polimórficos; VOs hidratados via factory en application layer. 6 migraciones cronológicas. 2 comandos console (seed idempotente + import transaccional). Admin read-only Twig. TDD estricto: test primero por cada VO, entidad, comando.

## Árbol de directorios

```
app/src/Training/
├── Entity/
│   ├── Mesocycle.php
│   ├── SessionTemplate.php
│   ├── ExerciseCatalog.php
│   ├── PlannedExercise.php
│   ├── PlannedSetGroup.php
│   ├── Session.php
│   └── SetLog.php
├── Enum/
│   ├── MesocycleGoal.php
│   ├── SetGroupType.php
│   ├── SetModifier.php
│   ├── MuscleGroup.php
│   └── Equipment.php
├── Domain/
│   ├── ValueObject/
│   │   ├── RepsScheme/
│   │   │   ├── RepsScheme.php        (abstract)
│   │   │   ├── RepsSchemeFactory.php
│   │   │   ├── StraightScheme.php
│   │   │   ├── AmrapScheme.php
│   │   │   ├── DescendingScheme.php
│   │   │   ├── ClusterScheme.php
│   │   │   ├── RestPauseScheme.php
│   │   │   ├── PapScheme.php
│   │   │   ├── SupersetScheme.php
│   │   │   ├── PoliquinTrisetScheme.php
│   │   │   └── ScdScheme.php
│   │   ├── RirScheme.php
│   │   └── WeekProgression.php
│   └── Exception/
│       └── InvalidSchemeException.php
├── Repository/                       (uno por entity agregado)
│   ├── MesocycleRepository.php
│   ├── ExerciseCatalogRepository.php
│   ├── SessionRepository.php
│   └── SetLogRepository.php
├── Command/
│   ├── SeedExercisesCommand.php
│   └── ImportMesocycleCommand.php
├── Service/
│   ├── ExerciseSlugifier.php
│   └── MesocycleImporter.php
├── Controller/
│   └── AdminMesocycleController.php
└── DataFixtures/                     (no bundle; solo JSON loader)
    └── (vacío; fixtures JSON viven en app/config/fixtures/)

app/migrations/
├── Version20260420120000_training_create_exercise_catalog.php
├── Version20260420120100_training_create_mesocycle.php
├── Version20260420120200_training_create_session_template.php
├── Version20260420120300_training_create_planned_exercise.php
├── Version20260420120400_training_create_planned_set_group.php
└── Version20260420120500_training_create_session_and_set_log.php

app/templates/admin/mesocycle/
├── index.html.twig
└── show.html.twig

app/config/fixtures/
├── exercises.json
└── meso-17.json                      (opcional; test/dev uses meso-17.test.json)
```

## Doctrine mapping (snippets clave)

```php
#[ORM\Entity(repositoryClass: MesocycleRepository::class)]
#[ORM\Table(name: 'training_mesocycle')]
final class Mesocycle {
    #[ORM\Id, ORM\Column(type: UuidType::NAME), ORM\GeneratedValue(strategy: 'CUSTOM'), ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 80)]
    private string $coach;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $startDate;

    #[ORM\Column(length: 32, enumType: MesocycleGoal::class)]
    private MesocycleGoal $goal;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $structureNote = null;

    #[ORM\OneToMany(mappedBy: 'mesocycle', targetEntity: SessionTemplate::class, cascade: ['persist', 'remove'])]
    private Collection $sessionTemplates;
}
```

```php
#[ORM\Entity] #[ORM\Table(name: 'training_planned_set_group')]
final class PlannedSetGroup {
    #[ORM\Column(length: 32, enumType: SetGroupType::class)]
    private SetGroupType $type;

    #[ORM\Column(type: Types::JSON)]
    private array $repsSchemeRaw;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rirSchemeRaw = null;

    #[ORM\Column(length: 16, enumType: SetModifier::class)]
    private SetModifier $modifier = SetModifier::NONE;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'linked_set_group_id', referencedColumnName: 'id', nullable: true)]
    private ?self $linkedSetGroup = null;

    public function getRepsScheme(): RepsScheme {
        return RepsSchemeFactory::fromArray($this->type, $this->repsSchemeRaw);
    }
}
```

```php
// ExerciseCatalog.muscleGroups[] — array<MuscleGroup>
#[ORM\Column(type: Types::JSON)]
private array $muscleGroupsRaw;  // string[]

public function getMuscleGroups(): array {
    return array_map(fn (string $v) => MuscleGroup::from($v), $this->muscleGroupsRaw);
}
```

## Jerarquía de VOs

```php
abstract readonly class RepsScheme {}

final readonly class StraightScheme extends RepsScheme {
    public function __construct(public string $reps) {}  // "9-13"
}

final readonly class AmrapScheme extends RepsScheme {
    public function __construct(public int $target, public int $min) {}
}

final readonly class DescendingScheme extends RepsScheme {
    /** @param int[] $drops */
    public function __construct(public array $drops) {}
}
// ... idem ClusterScheme, RestPauseScheme, PapScheme, SupersetScheme, PoliquinTrisetScheme, ScdScheme

final class RepsSchemeFactory {
    public static function fromArray(SetGroupType $type, array $raw): RepsScheme {
        return match ($type) {
            SetGroupType::STRAIGHT => new StraightScheme($raw['reps'] ?? throw new InvalidSchemeException('reps required')),
            SetGroupType::AMRAP => new AmrapScheme($raw['target'] ?? throw …, $raw['min'] ?? throw …),
            // ...
        };
    }
}
```

## Flujo transaccional del import

```
  JSON file                    MesocycleImporter              EntityManager             Postgres
     │                              │                              │                       │
     │ read + json_decode           │                              │                       │
     ├─────────────────────────────▶│                              │                       │
     │                              │ validateSchema()             │                       │
     │                              │ (uses RepsSchemeFactory on   │                       │
     │                              │  each setGroup)              │                       │
     │                              │                              │                       │
     │                              │ wrapInTransaction(function){ │                       │
     │                              ├─────────────────────────────▶│                       │
     │                              │                              │ persist Mesocycle     │
     │                              │                              ├──────────────────────▶│
     │                              │                              │ persist SessionTpl..  │
     │                              │                              ├──────────────────────▶│
     │                              │                              │ flush                 │
     │                              │                              ├──────────────────────▶│
     │                              │◀─────────────────────────────┤ commit or rollback    │
     │                              │ }                            │                       │
```

Cualquier `InvalidSchemeException` lanzada durante `validateSchema()` (antes de `wrapInTransaction`) aborta sin tocar DB. Cualquier error dentro de la closure dispara `rollback` automático por Doctrine.

## Contrato JSON del import

```json
{
  "mesocycle": {
    "coach": "Alex Hornero",
    "startDate": "2026-04-20",
    "endDate": "2026-05-25",
    "weeks": 5,
    "goal": "HYROX",
    "structureNote": "3+1 adherencia total…"
  },
  "sessionTemplates": [
    {
      "label": "Sesión 1",
      "orderInWeek": 1,
      "hasCardioBlock": false,
      "plannedExercises": [
        {
          "letter": "A",
          "orderInSession": 1,
          "exerciseSlug": "press-militar-maquina-placas",
          "technicalNotes": "Codos completamente extendidos…",
          "setGroups": [
            {
              "orderInExercise": 1,
              "type": "AMRAP",
              "series": 3,
              "repsScheme": {"target": 8, "min": 7},
              "rirScheme": {"pattern": "12→01→01"},
              "restSeconds": 120,
              "modifier": "NONE"
            }
          ]
        }
      ]
    }
  ]
}
```

## Architecture Decisions

| Decision | Choice | Rejected | Rationale |
|----------|--------|----------|-----------|
| jsonb mapping | `Types::JSON` + VO factory en getter | Custom Doctrine Type | Zero custom type; test en unit layer sin ORM; VO hidratado solo en application; menos acoplamiento |
| Polimorfismo `PlannedSetGroup` | Tabla única + enum discriminator + jsonb | STI Doctrine (10 subclases) | 1 entidad, 1 migración, queries directas; VO factory da type safety sin herencia |
| Carga de Meso 17 | Comando + JSON + vista read-only | EasyAdmin 4 completo | Meso = ~300 set groups; 1 JSON << 300 forms; re-usable con parser PDF de Fase 5 |
| Seed catálogo | JSON + comando idempotente | DoctrineFixturesBundle | Sin dependencia dev extra; portable; mismo schema que usará el parser |

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | VOs, factory, enums, slugifier | Pest puro; RED→GREEN→REFACTOR por task |
| Integration | Repositorios, comandos, Importer | Pest + Symfony KernelTestCase + DB real (transaction rollback entre tests) |
| Functional | `/admin/mesocycles` | Pest + WebTestCase contra stack docker |

`strict_tdd: true` → TDD Evidence table obligatoria en apply-progress.

## Migration / Rollout

Change aditivo. 6 migraciones `up()` crean tablas; `down()` las drop. Sin datos productivos (single-user, pre-prod). Meso 17 se importa manualmente una vez; si hace falta re-importar, `DELETE FROM training_mesocycle CASCADE` + comando.

## Open Questions

None. Decisiones resueltas en exploration + proposal.
