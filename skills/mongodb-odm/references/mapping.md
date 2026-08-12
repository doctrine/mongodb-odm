# Document Mapping

## Table of contents

1. Mapping drivers
2. Core attributes (`#[Document]`, `#[Id]`, `#[Field]`, `#[Index]`, and the rest)
3. Minimal example
4. Field types
5. Inheritance
6. Gotchas

## 1. Mapping Drivers

ODM supports three interchangeable drivers — **PHP attributes**, **XML**, and raw PHP code — cached identically regardless of choice, so no runtime difference. This guide uses attributes, the recommended driver (Doctrine annotations are deprecated).

```php
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
```

Attributes follow the PER Coding Style with **named arguments** — constructor argument names are covered by Doctrine's BC promise.

## 2. Core Attributes

### `#[Document]`

Required on every document class.

```php
#[Document(db: 'documents', collection: 'users', repositoryClass: MyProject\UserRepository::class, readOnly: true)]
class User { /* ... */ }
```

Options: `db`, `collection` (defaults to the class's short name), `repositoryClass`, `readOnly` (insert/upsert/remove only, no updates), `writeConcern`. Set the default database globally instead: `$config->setDefaultDB('my_db')`.

### `#[Id]`

```php
#[Document]
class User
{
    #[Id]
    protected string $id;
}
```

Gotcha: **cannot be `readonly`** — the id generator sets it from outside the class, which PHP forbids for `readonly`.

Strategies (`#[Id(strategy: '...')]`): `AUTO` (default — ObjectId, or a Symfony UUID if the property is UUID-typed), `ALNUM`, `CUSTOM` (via `class` option), `INCREMENT`, `UUID` (**deprecated** — prefer `AUTO` with a `uuid`-typed field, or `CUSTOM`), `NONE` (set the id manually before persisting).

```php
#[Id(strategy: 'CUSTOM', type: 'string', options: ['class' => \Vendor\Specific\Generator::class])]
private string $id;
```

```php
class Generator implements \Doctrine\ODM\MongoDB\Id\IdGenerator
{
    public function generate(DocumentManager $dm, object $document)
    {
        return 'my_generated_id';
    }
}
```

### `#[Field]`

```php
#[Field(type: 'string', name: 'co', nullable: false, notSaved: false)]
protected string $country; // stored as "co"
```

Options: `type` (defaults to what's inferred from the PHP property type, §4), `enumType` (auto-detected for backed enums), `name` (db field name), `nullable` (see gotcha below), `notSaved` (loaded but never written back — useful mid-migration).

### `#[Index]` / `#[UniqueIndex]`

```php
#[Document]
#[Index(keys: ['username' => 'desc'], options: ['unique' => true])]
class User {}
```

`keys` (class-level, required) maps field → order (`asc`/`desc`/`1`/`-1`) or a special type (`2dsphere`, `text`). `#[UniqueIndex]` is `#[Index]` with `unique` defaulted true. Full option set (TTL, partial, sparse, background) in `indexes-search.md`. `#[Indexes]` is deprecated since 2.2 — repeat class-level `#[Index]` instead.

### Other attributes worth knowing

- `#[EmbeddedDocument]`, `#[EmbedOne]`/`#[EmbedMany]`, `#[ReferenceOne]`/`#[ReferenceMany]` — `references-embedding.md`.
- `#[AlsoLoad]` — fall back to another field/method for schema migrations — `storage-transactions-lifecycle.md`.
- `#[ChangeTrackingPolicy]`, `#[Lock]`, `#[Version]` — `storage-transactions-lifecycle.md`.
- `#[HasLifecycleCallbacks]` — **required** for `#[PrePersist]`/`#[PostLoad]`/etc. method attributes to be honored at all.
- `#[ShardKey]`, `#[TimeSeries]`, `#[SearchIndex]`, `#[VectorSearchIndex]`, `#[Encrypt]` — their own reference files.
- `#[MappedSuperclass]`, `#[InheritanceType]`, `#[DiscriminatorField]`, `#[DiscriminatorMap]`, `#[DefaultDiscriminatorValue]` — §5.
- `#[QueryResultDocument]` — non-persisted class hydrating aggregation results (`querying-aggregation.md`).
- `#[File]` and `#[File\*]` — GridFS mapping (`storage-transactions-lifecycle.md`).

## 3. Minimal Example

```php
<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document(db: 'my_db', collection: 'users')]
class User
{
    #[ODM\Id]
    public string $id;

    #[ODM\Field]
    public string $username;

    #[ODM\Field]
    public \DateTimeImmutable $createdAt;
}
```

Omitting `db`/`collection`/`strategy`/`type` falls back to defaults inferred from context (§4).

## 4. Field Types

Built-in: `bin*` (several binary subtypes), `bool`, `collection`, `custom_id`, `date`, `date_immutable`, `decimal128` (needs `ext-bcmath`), `file`, `float`, `hash`, `id`, `int`, `int64`, `key`, `object_id`, `raw`, `string`, `timestamp`, `uuid`, `vector_float32`, `vector_int8`, `vector_packed_bit` (need MongoDB PHP extension ≥ 2.2.0).

Notable: `collection` ↔ numerically-indexed array. `date`/`date_immutable` ↔ `UTCDateTime`. `hash` ↔ a MongoDB object, but **values are passed to the driver unconverted** — no arbitrary PHP objects (e.g. `\DateTime`) inside a `hash` array; use a driver-native value or an embedded document instead.

### Dates: never store as strings

```php
#[Field]
public \DateTimeImmutable $immutableDateTime; // preferred
```

Prefer `DateTimeImmutable`; assign a new instance to change a date rather than mutating.

### Autodetection

`type` can be omitted and is inferred: `DateTime`→`date`, `DateTimeImmutable`→`date_immutable`, `array`→`hash`, `bool`/`float`/`int`/`string`→same, `Symfony\Component\Uid\Uuid`→`uuid`. Backed enums auto-detect too (`type` matches the backing type, `enumType` set to the enum FQCN).

Gotcha: **a nullable PHP type does not imply `nullable: true`.** Without it, `null` triggers `$unset` instead of storing `null`.

### Custom types

```php
class DateTimeWithTimezoneType extends \Doctrine\ODM\MongoDB\Types\Type
{
    use \Doctrine\ODM\MongoDB\Types\ClosureToPHP;

    public function convertToPHPValue($value): DateTimeImmutable { /* ... */ }
    public function convertToDatabaseValue($value): array { /* ... */ }
}

Type::addType('date_with_timezone', DateTimeWithTimezoneType::class);      // errors if already registered
Type::overrideType('date_immutable', DateTimeWithTimezoneType::class);     // errors if NOT already registered
Type::registerType('date_immutable', DateTimeWithTimezoneType::class);     // no check
```

`convertToDatabaseValue()` is never called for `NULL` values, and `UnitOfWork` never passes unchanged values to it. You can also register a type keyed by a value-object's FQCN, letting ODM auto-select it and letting you omit `type` in `#[Field]` entirely.

## 5. Inheritance

**Mapped superclass** — not itself a document, not queryable, just shared mapping:

```php
#[MappedSuperclass]
abstract class BaseDocument { /* shared fields */ }
```

**Single collection inheritance** — all subclasses share one collection via a discriminator:

```php
#[Document]
#[InheritanceType('SINGLE_COLLECTION')]
#[DiscriminatorField('type')]
#[DiscriminatorMap(['person' => Person::class, 'employee' => Employee::class])]
class Person { /* ... */ }

#[Document]
class Employee extends Person { /* ... */ }
```

`$dm->find(Person::class, $id)` returns the correct subclass. Gotcha: an unlisted class throws `MappingException` — every class sharing the collection must be in the map. For legacy pre-discriminator data, add `#[DefaultDiscriminatorValue('person')]` (value must be a key already in the map). Gotcha: incompatible with queryable encryption's `#[Encrypt(queryType: ...)]`.

**Collection-per-class inheritance** — each class gets its own collection with all inherited fields, no discriminator:

```php
#[InheritanceType('COLLECTION_PER_CLASS')]
class Person { /* ... */ }
```

Deprecated since 2.17, with no replacement: every document class is already mapped to its own collection by default, so this `InheritanceType` mapping can simply be removed rather than migrated to something else.

**Sharing a collection without inheritance** — repeat the same `collection`/`discriminatorField`/`discriminatorMap` on unrelated classes; query across both via `$dm->createQuery([Article::class, Album::class])`.

## 6. Gotchas

1. **`#[Id]` can't be `readonly`.**
2. **Never store dates as strings.**
3. **Prefer `DateTimeImmutable`** — assign, don't mutate.
4. **Nullable PHP type ≠ `nullable` mapping** — without it, `null` triggers `$unset`.
5. **`hash` values pass to the driver unconverted** — no arbitrary PHP objects inside.
6. **Discriminator maps must list every class sharing a collection.**
7. **`#[DefaultDiscriminatorValue]` must be a key already in the map.**
8. **`SINGLE_COLLECTION` + queryable encryption don't mix.**
9. **`UUID` id strategy is deprecated** — prefer `AUTO` + `uuid` field, or `CUSTOM`. **`COLLECTION_PER_CLASS` inheritance is deprecated since 2.17**, with no replacement — just remove the `InheritanceType` mapping.
10. **`#[Lock]`/`#[Version]` can't combine with `#[Id]`**; `#[Lock]` is `int`-only, `#[Version]` needs a `Versionable` type.
11. **Lifecycle callback attributes need `#[HasLifecycleCallbacks]`** on the class or they're silently ignored.
12. **`#[Indexes]` is deprecated** — repeat class-level `#[Index]`.
13. **An embedded index's `name` gets prefixed with the embedded field path** — can exceed MongoDB's length limit.
14. **A mapped superclass isn't queryable.**
15. **Embedded documents can't declare their own `db`/`collection`** — always inherit the parent's, though the same embeddable class can be reused across multiple parents.
