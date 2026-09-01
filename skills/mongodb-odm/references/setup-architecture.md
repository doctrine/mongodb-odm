# Core Setup & Architecture

## 1. Installation

```console
$ composer require doctrine/mongodb-odm
```

Requirements: `php: ^8.1`, `ext-mongodb: ^1.21 || ^2.0`, `mongodb/mongodb: ^1.21.2 || ^2.1.1`, `doctrine/collections: ^2.1 || ^3.1`, `doctrine/persistence: ^3.2 || ^4`, `friendsofphp/proxy-manager-lts: ^1.0` (lazy-loading proxies in 2.x), `symfony/console: ^5.4 || ^6.4 || ^7.0 || ^8.0`. The MongoDB PHP extension must be installed separately — Composer doesn't install it.

## 2. Bootstrapping a DocumentManager

**Keep the `spl_autoload_register(...)` line** — see gotcha below.

```php
<?php

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;

$config = new Configuration();
$config->setProxyDir(__DIR__ . '/generated/proxies');
$config->setProxyNamespace('Proxies');
$config->setHydratorDir(__DIR__ . '/generated/hydrators');
$config->setHydratorNamespace('Hydrators');
$config->setDefaultDB('doctrine_odm');
$config->setMetadataDriverImpl(AttributeDriver::create(__DIR__ . '/src/Documents'));

$dm = DocumentManager::create(config: $config);

spl_autoload_register($config->getProxyManagerConfiguration()->getProxyAutoloader());
```

- `Configuration` is required by the `DocumentManager::create()` **factory method** — never `new DocumentManager(...)` directly.
- Passing no client (or `null`) makes ODM create its own, with the correct BSON typemap.
- The `spl_autoload_register(...)` call is load-bearing in 2.16: without it, proxy classes regenerate on every request.

### Providing your own client

```php
<?php

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Client;

$client = new Client('mongodb://127.0.0.1', [], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);
$dm = DocumentManager::create($client, $config);
```

`typeMap: DocumentManager::CLIENT_TYPEMAP` is required for ODM to handle BSON results correctly.

## 3. Core Architecture

### Document lifecycle states

**NEW** (just `new`'d, no persistent identity) → **MANAGED** (tracked by a `DocumentManager`) → **REMOVED** (will be deleted on next commit), or **DETACHED** (has an identity but isn't tracked, e.g. after `clear()`).

### Transactional write-behind

The biggest shift from an ActiveRecord mindset: the `DocumentManager` delays query execution to run at the end of a transaction, holding write locks as briefly as possible. Work with objects as usual, and call `flush()` when done.

**Do not `flush()` after every change** — batch into units of work, flush 0–2 times per HTTP request.

### UnitOfWork

The `DocumentManager` delegates change-tracking to an internal `UnitOfWork`, rarely touched directly:

```php
$size = $dm->getUnitOfWork()->size(); // number of managed documents — worth checking in dev
```

A new unit of work starts implicitly on `DocumentManager` creation or right after `flush()`; `$dm->close()` discards it (unpersisted changes are lost).

### persist() / remove() / flush()

```php
$user = new User();
$user->setUsername('jwage');
$dm->persist($user);
$dm->flush();
```

Neither `persist()` nor `remove()` issues an immediate query — nothing hits the database before `flush()`.

`persist($document)`: NEW → MANAGED (inserted on flush); MANAGED → no-op but cascades if mapped; REMOVED → MANAGED again; **DETACHED → undefined behavior, never do this.**

`remove($document)`: NEW → ignored (cascades if mapped); MANAGED → REMOVED; DETACHED → throws `InvalidArgumentException`; REMOVED → no-op.

The identifier is generated during `persist()` if not already set — don't rely on it inside a `prePersist` listener.

### detach / merge / clear

```php
$dm->detach($document);
$document = $dm->merge($detachedDocument); // use the RETURN VALUE, not the original
$dm->clear();            // detach everything
$dm->clear(User::class); // DEPRECATED since 2.4, unsupported in 3.0 — do not use; call $dm->clear()
```

### Flush options / cascading

```php
$dm->flush(['writeConcern' => new \MongoDB\Driver\WriteConcern(1)]);
```

No operation cascades to references by default — opt in per relationship: `#[ReferenceMany(targetDocument: Address::class, cascade: ['persist', 'remove'])]`. Valid values: `persist`, `remove`, `merge`, `detach`, `refresh`, `all`. Don't blindly apply `cascade: ['all']` everywhere — it costs performance. (Embeds always cascade automatically — no option needed.)

### Querying, simplest to most powerful

```php
$user  = $dm->find(User::class, $id);
$users = $dm->getRepository(User::class)->findBy(['age' => 20]);
$users = $dm->createQueryBuilder(User::class)->field('age')->range(20, 30)->getQuery()->execute();
```

### Document class rules

Not `final`, no `final` methods. Persistent properties should be **private/protected** (public can break lazy-loading). No re-declaring a mapped property already on an ancestor. Collection-valued fields typed against `Collection`, not `array`. **Doctrine never calls document constructors** — only your own `new Document(...)` does.

## 4. Repositories

```php
$repository = $dm->getRepository(User::class); // DocumentRepository by default
$disabledUsers = $repository->findBy(['disabled' => true]);
```

Methods: `find()`, `findAll()`, `findBy()`, `findOneBy()`, `matching()` (Criteria API) — all apply configured Filters.

```php
#[Document(repositoryClass: \Repositories\UserRepository::class)]
class User { /* ... */ }

class UserRepository extends \Doctrine\ODM\MongoDB\DocumentRepository
{
    public function findDisabled(): array
    {
        return $this->findBy(['disabled' => true]);
    }
}
```

Change the default repository class project-wide: `$dm->getConfiguration()->setDefaultRepositoryClassName(MyDefaultRepository::class);`. To inject extra dependencies into a repository, implement a custom `RepositoryFactory` (extending `AbstractRepositoryFactory`) via `$config->setRepositoryFactory(...)` rather than widening `DocumentRepository`'s constructor.

## 5. Console Commands

| Command | Purpose |
|---|---|
| `odm:schema:create` | Create collections/indexes (`--collection`, `--index`, `--search-index`, `--background`, `-c/--class`) |
| `odm:schema:update` | Update indexes only |
| `odm:schema:drop` | Drop databases/collections/indexes |
| `odm:schema:shard` | Enable sharding for `#[ShardKey]` documents |
| `odm:query` | Query MongoDB and inspect results |
| `odm:generate:hydrators` / `odm:generate:proxies` | Generate hydrator/proxy classes |
| `odm:clear-cache:metadata` | Clear the metadata cache |

```php
$helperSet = new \Symfony\Component\Console\Helper\HelperSet([
    'dm' => new \Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper($dm),
]);

$app = new \Symfony\Component\Console\Application('Doctrine MongoDB ODM');
$app->setHelperSet($helperSet);
$app->addCommands([
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\CreateCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\UpdateCommand(),
    new \Doctrine\ODM\MongoDB\Tools\Console\Command\QueryCommand(),
]);
$app->run();
```

A framework's Doctrine bundle (e.g. `doctrine/mongodb-odm-bundle` for Symfony) wires these for you.

## 6. Gotchas

1. **Never call `flush()` per mutation** — batch, flush 0–2 times per request.
2. **`persist()`/`remove()` are not immediate** — nothing hits the DB until `flush()`.
3. **Never pass a detached document to `persist()`** — undefined behavior. Use `merge()` and its return value.
4. **`remove()` on a detached document throws**, it doesn't silently no-op.
5. **The identifier may not exist yet inside `prePersist`.**
6. **Persistent fields should be private/protected**, not public — public properties can silently break lazy-loading.
7. **Doctrine never invokes document constructors.**
8. **A loaded `Collection` becomes a `PersistentCollection`**, not the plain `ArrayCollection` you constructed — it lazy-loads references and tracks changes.
9. **Cascade defaults to nothing for references** (embeds are the exception — always cascade).
10. **The proxy autoloader registration is required in 2.16** — omitting it regenerates proxy classes every request.
11. **Avoid serializing managed documents holding proxy references** — proxy `__sleep` can't return private parent-class property names, risking silently corrupted data.
