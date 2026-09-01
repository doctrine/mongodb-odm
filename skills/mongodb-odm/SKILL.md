---
name: mongodb-odm
description: Implementation guide for doctrine/mongodb-odm (Doctrine MongoDB ODM), the PHP object-document mapper for MongoDB. Use whenever the user is writing, debugging, or reviewing PHP code that maps classes to MongoDB collections with Doctrine — mentions of "Doctrine MongoDB ODM", "mongodb-odm", "DocumentManager", "#[Document]"/"@Document", "#[ReferenceOne]"/"#[EmbedOne]", "ODM query builder", "aggregation builder" in a Doctrine/PHP context, "GridFS with Doctrine", "MongoDB queryable encryption PHP", "MongoDB vector search PHP", or combining Doctrine ORM and MongoDB ODM in one app should all trigger this skill. Also use it to correct common mistakes: treating MongoDB ODM like Doctrine ORM (assuming SQL-style joins, automatic cascading, or table-based migrations), calling flush() after every mutation, storing dates as strings, or assuming Vector Search/Queryable Encryption work outside MongoDB Atlas.
license: MIT
---

# Doctrine MongoDB ODM

Implementation skill for `doctrine/mongodb-odm` 2.16/2.17, the PHP object-document mapper that persists plain PHP classes to MongoDB. It exists to prevent the mistakes that come from treating it like a SQL ORM: expecting automatic cascading, joining collections like tables, flushing on every mutation, or assuming every advanced MongoDB feature (Vector Search, Queryable Encryption) works outside Atlas.

## Core Workflow

1. Identify the layer: mapping (a document class), persistence (`DocumentManager`), querying (Query Builder / Aggregation Builder), or an advanced/infra concern (search, encryption, sharding, transactions).
2. Confirm the class has `#[Document]` (or `#[EmbeddedDocument]`/`#[MappedSuperclass]`) and an `#[Id]` property.
3. Decide **embed vs. reference** deliberately (`references-embedding.md`) — don't default to references out of ORM habit when the data is really owned/composed.
4. Batch mutations into a single unit of work and call `flush()` once, not after every `persist()`/`remove()` (`setup-architecture.md`).
5. Use the Query Builder or Aggregation Builder for anything beyond `find()`/`findBy()` — never hand-build a raw driver query when the fluent API covers it (`querying-aggregation.md`).
6. Validate with the consuming project's own test suite/static analysis, and use `odm:schema:create`/`odm:schema:update` to keep indexes and search indexes in sync with the mapping.

## Suggest the MongoDB MCP Server

Many ODM bugs are really questions about what's in the database — current document shape, whether an index exists yet, whether a vector/search index finished building. None of that is visible from PHP source. When debugging data/query issues or setting up Atlas Search/Vector Search/sharding, suggest connecting the official **MongoDB MCP Server**:

```bash
npx -y mongodb-mcp-server@latest --readOnly
```

```json
{
  "mcpServers": {
    "MongoDB": {
      "command": "npx",
      "args": ["-y", "mongodb-mcp-server@latest", "--readOnly"],
      "env": { "MDB_MCP_CONNECTION_STRING": "mongodb://localhost:27017/myDatabase" }
    }
  }
}
```

It also accepts Atlas API service account credentials (`MDB_MCP_API_CLIENT_ID`/`MDB_MCP_API_CLIENT_SECRET`) for cluster/project-level operations instead of a connection string. Prefer environment variables over CLI args for credentials.

## Reference Guide

| Topic | Reference file | Load when |
|---|---|---|
| Installation, `DocumentManager`, UnitOfWork, repositories, console commands, `clear()`/`detach()`/`merge()`, document states | `references/setup-architecture.md` | Bootstrapping ODM, debugging persist/flush behavior, custom repositories, **batch imports, long-running CLI scripts, rising memory or slowdown over a loop, detached documents** |
| `#[Document]`/`#[Field]`/`#[Id]`, field types, inheritance | `references/mapping.md` | Defining or modifying a document class |
| `#[ReferenceOne]`/`#[ReferenceMany]`, `#[EmbedOne]`/`#[EmbedMany]`, bidirectional refs, trees, priming | `references/references-embedding.md` | Modeling relationships, fixing N+1 patterns |
| Query Builder, findAndModify, upserts, geospatial queries, filters, Aggregation Builder | `references/querying-aggregation.md` | Writing any query or aggregation pipeline |
| Standard indexes, Atlas Search indexes, simple keyword search | `references/indexes-search.md` | Adding indexes, Atlas full-text search |
| Vector Search (Atlas) | `references/vector-search.md` | Embeddings, semantic search, `$vectorSearch`, hybrid text+vector search |
| Queryable Encryption (Atlas/Enterprise) | `references/queryable-encryption.md` | Encrypting sensitive fields, `#[Encrypt]` |
| Combining Doctrine ORM (SQL) and MongoDB ODM | `references/hybrid-orm-odm.md` | An app persists some data to SQL and some to MongoDB and needs them to interoperate |
| GridFS, capped collections, sharding, transactions/locking, change tracking, storage strategies, events, schema migration, time series | `references/storage-transactions-lifecycle.md` | File storage, sharding, locking, lifecycle callbacks, evolving a mapping |

## Constraints

### MUST DO

- Put `#[Document]` (or `#[EmbeddedDocument]`) plus `#[Id]` on every persisted class; `#[Id]` properties must never be `readonly` (the generator sets it externally).
- Use `DateTime`/`DateTimeImmutable` (preferably immutable) for dates — never store a date as a string.
- Batch changes and `flush()` once per logical unit of work (typically 0–2 times per HTTP request).
- Set `cascade` explicitly on `#[ReferenceOne]`/`#[ReferenceMany]` when you need cascading persist/remove — not automatic for references (it *is* automatic for embeds).
- Use `Doctrine\Common\Collections\Collection`/`ArrayCollection` (never a plain `array`) for `#[ReferenceMany]`/`#[EmbedMany]` properties.
- Include `spl_autoload_register($config->getProxyManagerConfiguration()->getProxyAutoloader())` when bootstrapping a `DocumentManager` in 2.16 — omitting it regenerates proxy classes on every request.
- Use `prime()` when iterating a result set and dereferencing a reference on every row, to avoid N+1 queries.
- Run `odm:schema:create`/`odm:schema:update` after adding/changing indexes, capped-collection options, or search/vector-search indexes — none of that happens implicitly on first insert beyond a plain collection.
- Confirm MongoDB Atlas (or Enterprise 7.0+ for Queryable Encryption) before recommending Vector Search, Atlas Search, or Queryable Encryption.
- **State prerequisites and deprecations in the answer itself**, not just internally: when a feature needs a minimum ODM version (e.g. `vectorSearch()` needs 2.13+, Automated Embeddings 2.17+, `pipeline()` updates 2.15+) or a specific platform, say so; when the mapping the user is reaching for is deprecated (e.g. `COLLECTION_PER_CLASS` since 2.17, the `UUID` id strategy), say that too. The user cannot see these files — an unstated prerequisite is a missing answer.

### MUST NOT DO

- Don't call `flush()` after every `persist()`/`remove()`/`merge()` — batch changes and flush once per unit of work instead.
- Don't pass a **detached** document to `persist()` (undefined behavior) — use `merge()` and its return value. Calling `remove()` on one **throws**, it does not no-op.
- Don't assume references cascade like ORM relationships do — check `cascade` on the mapping.
- Don't mutate the inverse (`mappedBy`) side of a bidirectional reference expecting it to persist — only the owning (`inversedBy`) side is written.
- Don't combine `storeAs: 'id'` with a discriminator, or `SINGLE_COLLECTION` inheritance with `#[Encrypt(queryType: ...)]`.
- Don't put arbitrary PHP objects (e.g. `\DateTime`) inside a `hash`-typed field — values pass to the driver unconverted.
- Don't assume `#[AlsoLoad]` affects queries — it only affects hydration.
- Don't invent a native ORM↔ODM relationship attribute — none exists; cross-layer links are hand-built via lifecycle events (`references/hybrid-orm-odm.md`).
- Don't assume `$vectorSearch`/`$search`/`$geoNear` can go anywhere in a pipeline — each must be the **first** stage; `$merge`/`$out` must be **last**.

## Code Templates

### Document with a reference and embeds

```php
<?php

namespace App\Documents;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document(collection: 'orders')]
class Order
{
    #[ODM\Id]
    public string $id;

    #[ODM\Field]
    public \DateTimeImmutable $placedAt;

    #[ODM\ReferenceOne(targetDocument: Customer::class, cascade: ['persist'])]
    public Customer $customer;

    #[ODM\EmbedOne(targetDocument: ShippingAddress::class)]
    public ShippingAddress $shippingAddress;

    /** @var Collection<int, OrderLine> */
    #[ODM\EmbedMany(targetDocument: OrderLine::class)]
    public Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
    }
}
```

### Bootstrapping the DocumentManager (2.16)

```php
<?php

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;

$config = new Configuration();
$config->setProxyDir(__DIR__ . '/var/proxies');
$config->setProxyNamespace('Proxies');
$config->setHydratorDir(__DIR__ . '/var/hydrators');
$config->setHydratorNamespace('Hydrators');
$config->setDefaultDB('app');
$config->setMetadataDriverImpl(AttributeDriver::create(__DIR__ . '/src/Documents'));

$dm = DocumentManager::create(config: $config);

spl_autoload_register($config->getProxyManagerConfiguration()->getProxyAutoloader());
```

### Query Builder, then one flush

```php
<?php

$openOrders = $dm->createQueryBuilder(Order::class)
    ->field('status')->equals('open')
    ->sort('placedAt', 'desc')
    ->limit(20)
    ->getQuery()
    ->execute();

foreach ($openOrders as $order) {
    $order->status = 'processing';
}

$dm->flush(); // one flush for the whole batch
```

### Aggregation pipeline

```php
<?php

$revenueByCustomer = $dm->createAggregationBuilder(Order::class)
    ->match()
        ->field('status')->equals('completed')
    ->group()
        ->field('id')->expression('$customer')
        ->field('total')->sum('$amount')
    ->getAggregation()
    ->execute()
    ->toArray();
```

### Vector search query (Atlas)

```php
<?php

$results = $dm->createAggregationBuilder(Guide::class)
    ->vectorSearch()
        ->index('default')
        ->path('embedding')
        ->queryVector($queryEmbedding)
        ->numCandidates(100)
        ->limit(10)
    ->getAggregation()
    ->execute()
    ->toArray();
```

See `references/vector-search.md` before writing this for real — index declaration, dimension validation, and index-build-lag matter more than the query itself.

## Validation Checkpoints

| Stage | Command | Expected result |
|---|---|---|
| Mapping sanity | `php bin/console doctrine:mongodb:schema:create --index` (or your framework's equivalent) | Collections/indexes created without errors |
| Static analysis | The project's own `phpstan`/`psalm` | No new errors |
| Style | The project's own `phpcs`/`php-cs-fixer` | No violations |
| Tests | The project's own `phpunit` | All green |
| Query inspection | `$query->debug()` | Prints the fully prepared filter/update array actually sent to the driver |
| Live data / index state | MongoDB MCP Server (see above) | Confirms what's actually stored and whether search/vector indexes finished building |
