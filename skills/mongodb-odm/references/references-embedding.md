# References, Embedding & Trees

Always type `#[ReferenceMany]`/`#[EmbedMany]` fields as `Doctrine\Common\Collections\Collection` (backed by `ArrayCollection`), never a plain PHP `array` — arrays can't be transparently wrapped for the `PersistentCollection` change-tracking mechanism.

## Table of contents

1. `#[ReferenceOne]` / `#[ReferenceMany]`
2. Bidirectional references
3. Complex references (custom criteria / inverse-loaded)
4. `#[EmbedOne]` / `#[EmbedMany]`
5. Tree structures
6. Priming references (solving N+1)
7. Gotchas

## 1. `#[ReferenceOne]` / `#[ReferenceMany]`

```php
#[Document]
class User
{
    /** @var Collection<int, Account> */
    #[ReferenceMany(targetDocument: Account::class)]
    private Collection $accounts;

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
    }
}
```

**`targetDocument`** — omit for mixed target types (§1.1).

**`storeAs`** — on-disk shape: `dbRef` (`$ref`+`$id`, **current default**), `dbRefWithDb` (+ `$db`, pre-2.0 default), `ref` (custom embedded object with an `id` field), `id` (just the identifier — leanest, but **incompatible with discriminators**, since there's no DBRef to carry one).

**`cascade`** — no operation cascades by default: `#[ReferenceOne(targetDocument: Profile::class, cascade: ['persist'])]`. Values: `all`, `detach`, `merge`, `refresh`, `remove`, `persist`.

**`mappedBy`/`inversedBy`** — owning vs. inverse side (§2).

**`orphanRemoval`** — deletes the target when the reference is removed from a privately-owning document. Gotcha: assumes the target is **not reused elsewhere** — reassigning an "orphaned" document to a different parent doesn't save it from deletion.

**`storeEmptyArray`** — an empty `ReferenceMany` stores nothing by default; set `true` to persist an actual empty array.

### 1.1 Mixing target types

Omit `targetDocument`; the class name is stored in `_doctrine_class_name` (customizable via `discriminatorField`), or use `discriminatorMap`/`defaultDiscriminatorValue` to avoid storing the FQCN. Gotcha: the mongo shell only shows `$id`/`$ref` of a DBRef, hiding `$db`/discriminator fields — verify via a driver.

## 2. Bidirectional References

Without `mappedBy`/`inversedBy`, both sides are independently owning, storing the reference redundantly. Prefer an explicit owning/inverse pair:

```php
#[Document]
class BlogPost
{
    #[ReferenceOne(targetDocument: User::class, inversedBy: 'posts')]
    private User $user;
}

#[Document]
class User
{
    /** @var Collection<int, BlogPost> */
    #[ReferenceMany(targetDocument: BlogPost::class, mappedBy: 'user')]
    private Collection $posts;
}
```

Gotcha: **the inverse (`mappedBy`) side is immutable** — always mutate the owning (`inversedBy`) side and flush.

One-to-one: break the relationship from the owning side (`$cart->customer = null; $dm->flush();`). Gotcha: an inverse one-to-one reference loads **eagerly** when the owning document hydrates (loading a `Customer` also loads its `Cart`) — a hidden cost hydrating many at once.

Self-referencing many-to-many:

```php
#[ReferenceMany(targetDocument: User::class, mappedBy: 'myFriends')]
public Collection $friendsWithMe;

#[ReferenceMany(targetDocument: User::class, inversedBy: 'friendsWithMe')]
public Collection $myFriends;
```

## 3. Complex References (custom criteria / inverse-loaded)

Immutable, mapping-only — no DBRef/id stored on the document. Available on both `ReferenceOne`/`ReferenceMany`: `criteria`, `sort`, `skip`, `limit`, `repositoryMethod`.

```php
#[ReferenceMany(targetDocument: Comment::class, mappedBy: 'blogPost', sort: ['date' => 'desc'], limit: 5)]
private Collection $last5Comments;

#[ReferenceMany(targetDocument: Comment::class, mappedBy: 'blogPost', criteria: ['isByAdmin' => true])]
private Collection $commentsByAdmin;
```

Fully custom loading:

```php
#[ReferenceMany(targetDocument: Comment::class, mappedBy: 'blogPost', repositoryMethod: 'findSomeComments')]
private Collection $someComments;

class CommentRepository extends \Doctrine\ODM\MongoDB\DocumentRepository
{
    public function findSomeComments(BlogPost $blogPost): \Doctrine\ODM\MongoDB\Iterator\Iterator
    {
        return $this->createQueryBuilder()->field('blogPost')->references($blogPost)->getQuery()->execute();
    }
}
```

Must return an `Iterator`; ODM passes the owning document as the method's first argument. Gotcha: combining `repositoryMethod` with a mapping-level `prime` (§6) means the mapping's `prime` overwrites any primer set up inside the repository method.

## 4. `#[EmbedOne]` / `#[EmbedMany]`

```php
#[Document]
class User
{
    #[EmbedOne(targetDocument: Address::class)]
    private ?Address $address;

    /** @var Collection<int, PhoneNumber> */
    #[EmbedMany(targetDocument: Phonenumber::class)]
    private Collection $phoneNumbers;
}

#[EmbeddedDocument]
class Address
{
    #[Field(type: 'string')]
    private string $street;
}
```

Mixing embedded types works the same as references (§1.1). **Cascading is automatic and not configurable**: embedded documents "cannot exist without" their parent by nature — there's no `cascade` option, unlike references which default to none. `storeEmptyArray: true` works the same as on `ReferenceMany`.

### Embed vs. reference — the actual tradeoff

Embed for true composition (cannot exist independently, e.g. `Address` on a `User`) — cascades are automatic. Reference for independent documents with their own lifecycle, where cascading must be opted into and the target may be queried/shared. `storeAs` trades storage size for flexibility (`dbRef` supports discriminators; `id` is leanest but doesn't). `orphanRemoval` approximates embedding's auto-delete, but only when the target is truly private to one parent.

## 5. Tree Structures

Four MongoDB patterns, each built from ordinary `Field`/`ReferenceOne`/`ReferenceMany`/`EmbedMany` — no dedicated "Tree" type.

**Full tree** (nested `EmbedMany`, e.g. `Comment` embedding `Comment[] $replies`) — slice large arrays instead of loading everything: `->selectSlice('replies', 0, 10)`.

**Parent reference** — `#[ReferenceOne(targetDocument: Category::class)] private ?Category $parent`; query children via `field('parent.id')->equals($id)`.

**Child reference** — `#[ReferenceMany(targetDocument: Category::class)] private Collection $children`; query the parent of a child via `field('children.id')->equals($id)`.

**Array of ancestors** — `#[ReferenceMany] private Collection $ancestors` alongside a `$parent` reference; query all descendants by matching any node listing the category in `ancestors`.

**Materialized paths** — a plain `#[Field] private string $path`; query the whole tree sorted by path, or a node's descendants via `field('path')->equals('/^a,b,/')`.

Each is a modeling recommendation, not an ODM data structure — pick based on your read/write pattern.

## 6. Priming References (solving N+1)

This is the **N+1 query problem**: `#[ReferenceOne]` hydrates as an uninitialized proxy and `#[ReferenceMany]` as an uninitialized `PersistentCollection`, each holding only the referenced id(s). Touching any property of the proxy, or iterating/counting the collection, transparently fires the query that loads it. So one query for the N parent documents plus one per dereference is N+1 queries, and nothing in the code looks like a query. Priming collapses the N into one.

Name this explicitly when diagnosing it — the symptom the user reports is usually "hundreds of extra queries" or a slow loop, not "lazy loading".

```php
$users = $dm->createQueryBuilder(User::class)
    ->field('accounts')->prime(true)
    ->limit(100)
    ->getQuery()->execute();
```

`prime(true)` loads all referenced accounts across the result set in one extra query. Works with id references and discriminated references (one query **per distinct class** for the latter, not one overall). **Requires hydration enabled** — disabling it returns raw DBRefs, defeating priming.

Mapping-level priming for inverse references (since 1.2): `#[ReferenceMany(targetDocument: Account::class, prime: ['user'])]`.

`prime()` also accepts a custom callable instead of `true`, receiving `(DocumentManager $dm, ClassMetadata $class, array $ids, array $hints)` for full control over the priming query (e.g. propagating read preference from `$hints`).

## 7. Gotchas

- **Mutating the inverse (`mappedBy`) side does nothing** — always change the owning side.
- **`orphanRemoval: true` assumes exclusive ownership.**
- **`storeAs: 'id'` forfeits discriminators.**
- **Inverse one-to-one references load eagerly**, not lazily.
- **The mongo shell hides DBRef fields other than `$ref`/`$id`.**
- **`storeAs` default changed in 2.0** (was `dbRefWithDb`, now `dbRef`) — mixed-version data may need a migration or explicit `storeAs`.
- **Priming requires hydration enabled.**
- **Priming discriminated/inherited references costs one query per class.**
- **`repositoryMethod` + mapping-level `prime`**: the mapping's `prime` wins, dropping any primer inside the repository method.
- **Empty reference/embed collections store nothing by default** — use `storeEmptyArray: true` if code expects `[]` to always exist.
