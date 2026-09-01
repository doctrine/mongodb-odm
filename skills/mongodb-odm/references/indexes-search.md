# Indexes & Atlas Search

For Atlas Vector Search, see `vector-search.md`.

## 1. Standard Indexes

```php
#[Document]
class User
{
    #[Field(type: 'string')]
    #[Index]
    public string $username;
}
```

`#[Index]` options: `name` (needed when the auto-generated one is too long), `background`, `expireAfterSeconds` (TTL), `order` (`asc`/`desc`), `unique`, `sparse` (with `unique`, allows duplicate `null`s while enforcing uniqueness on set values), `partialFilterExpression` (MongoDB 3.2+).

```php
#[Field(type: 'string')]
#[UniqueIndex(order: 'asc')] // shortcut for Index(unique: true)
public string $username;

// compound index — class-level
#[Document]
#[UniqueIndex(keys: ['accountId' => 'asc', 'username' => 'asc'])]
class User { /* ... */ }

// multiple indexes — repeat the class-level attribute
#[Document]
#[Index(keys: ['accountId' => 'asc'])]
#[Index(keys: ['username' => 'asc'])]
class User { /* ... */ }
```

Indexes declared inside an `#[EmbeddedDocument]` are pulled in when building indexes for the owning document. Gotchas: mixed-type embed relationships need a discriminator map before ODM can build their indexes; a `name` on an embedded index gets prefixed with the embedded field path, which can exceed MongoDB's length limit.

Geospatial: `#[Index(keys: ['coordinates' => '2d'])]` (or `'2dsphere'` for GeoJSON — see `querying-aggregation.md` §4 for which query methods pair with which). Partial (MongoDB 3.2+): `#[Index(keys: ['city' => 'asc'], partialFilterExpression: ['version' => ['$gt' => 1]])]`.

## 2. Creating / Syncing Indexes

```php
$dm->getSchemaManager()->ensureIndexes();
```

```console
$ php mongodb.php odm:schema:create --index
```

`odm:schema:create` flags: `--collection`, `--index`, `--search-index` (Atlas Search/Vector Search), `--skip-search-indexes`, `--background`, `-c/--class`. Related: `odm:schema:update` (indexes only), `odm:schema:drop`, `odm:schema:shard`.

## 3. Atlas Search Indexes

For MongoDB Atlas Search, queried via `$search`/`$searchMeta`. Rules: search indexes only go on **document classes**, never embedded documents; `fields` must use **actual database field names**, since ODM doesn't translate mapped property names here.

`#[SearchIndex]` options: `name` (default `"default"`), `dynamic` (auto-index all supported types vs. requiring `fields`), `fields`, `analyzer`, `searchAnalyzer`, `analyzers`, `storedSource`, `synonyms`.

```php
#[Document]
#[SearchIndex(
    name: 'usernameAndAddresses',
    fields: [
        'username' => [['type' => 'string'], ['type' => 'autocomplete']],
        'addresses' => ['type' => 'embeddedDocuments', 'dynamic' => true],
    ],
)]
class User { /* ... */ }
```

`addresses`, an embed-many collection, must use `embeddedDocuments` (dynamic mapping only works *inside* that).

```php
#[Document]
#[SearchIndex(dynamic: true)]
class BlogPost { /* ... */ }
```

Gotcha: dynamic mapping does **not** work for embedded documents in arrays — those always need an explicit static `embeddedDocument(s)` mapping. Dynamic indexes also use more disk space and can be slower — prefer them for changing schemas/prototyping.

## 4. Simple Keyword Search (no Atlas required)

A baseline array-field technique — not a substitute for Atlas Search beyond small/simple cases.

```php
#[Document]
class Product
{
    #[Field(type: 'collection')]
    #[Index]
    public array $keywords = [];
}

$qb = $dm->createQueryBuilder(Product::class)->field('keywords')->in($keywords);  // match ANY
$qb = $dm->createQueryBuilder(Product::class)->field('keywords')->all($keywords); // match ALL
```

Move to Atlas Search (§3) once you need better relevance ranking, stemming, or fuzzy matching. Blending full-text and vector relevance scores isn't built in — that's a custom aggregation (e.g. `$unionWith` plus manual score combination — see `vector-search.md`).

## 5. Gotchas

1. **Search index field names are raw database names**, not PHP properties.
2. **Dynamic Atlas Search mapping skips embed-many arrays** — use a static mapping there.
3. **Embedded regular-index names get path-prefixed** — can exceed the length limit.
4. **Mixed-type embed relationships need a discriminator map** before indexes build.
5. **Partial indexes require MongoDB 3.2+.**
6. **`--index` and `--search-index` are independent flags** on `odm:schema:create`.
