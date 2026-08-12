# Vector Search

Requires **Doctrine ODM 2.13+** and **MongoDB Atlas** — this does not work on self-managed/on-prem MongoDB.

Two ways to get embeddings into a vector index: generate and store them yourself (§1-4, works since 2.13), or let Atlas generate them automatically at index/query time (§1b, Automated Embeddings, needs 2.17+ and a registered Voyage AI key). Automated embeddings mean less application code — no embedding pipeline to run and store results from — but the manual path still applies if you already have an embedding provider/pipeline in place, need a model not offered by Atlas's automated embedding, or want to reuse the same stored vectors from outside MongoDB.

## 1. Generate embeddings yourself (outside ODM)

Any external embedding provider works, as long as you get a fixed-dimension `float[]` (or `int[]`/`bool[]`). Example using Symfony AI + Voyage AI:

```php
$platform = \Symfony\AI\Platform\Bridge\Voyage\PlatformFactory::create(getenv('VOYAGE_API_KEY'));
$vectors = $platform->invoke('voyage-3', $text)->asVectors();
```

## 1b. Automated Embeddings (2.17+) — skip manual embedding generation

Instead of generating embeddings yourself and mapping a vector field, use an `autoEmbed` index field pointing at a plain text field. Atlas embeds the content automatically (hosted Voyage AI model) on write and re-embeds the query text on read — no embedding pipeline, no stored vector field at all.

```php
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document]
#[ODM\VectorSearchIndex(
    fields: [
        ['type' => 'autoEmbed', 'path' => 'content', 'modality' => 'text', 'model' => 'voyage-4-large'],
        ['type' => 'filter', 'path' => 'published'],
    ],
)]
class Article
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field]
    public string $content = '';

    #[ODM\Field]
    public bool $published = false;
}
```

No `numDimensions`/`similarity` needed — inferred from the model. Insert documents normally (`$dm->persist($article); $dm->flush();`), then create/sync the index exactly as in §3. Querying uses `query()` (plain text) instead of `queryVector()`:

```php
$results = $dm->createAggregationBuilder(Article::class)
    ->vectorSearch()
        ->index('default')
        ->path('content')
        ->query('semantic search NoSQL')
        ->numCandidates(10)
        ->limit(5)
    ->getAggregation()->execute()->toArray();
```

All `voyage-4` models produce compatible embeddings — index with a higher-quality model and query with a lighter one to cut query cost without reindexing: `->vectorSearch()->query('...')->model('voyage-4-lite')`. Automated embedding still requires Atlas and a registered Voyage AI API key; it doesn't work on standalone deployments any more than the manual path does.

## 2. Declare the index and the field (manual embeddings)

```php
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;

#[ODM\Document]
#[ODM\VectorSearchIndex(
    fields: [
        ['type' => 'vector', 'path' => 'voyage3Vector', 'numDimensions' => 1024, 'similarity' => ClassMetadata::VECTOR_SIMILARITY_DOT_PRODUCT],
        ['type' => 'filter', 'path' => 'published'],
    ],
    name: 'default',
)]
class Guide
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field]
    public bool $published = false;

    /** @var list<float>|null */
    #[ODM\Field(type: Type::COLLECTION)]
    public ?array $voyage3Vector = null;

    /** @param list<float> $vector */
    public function setVoyage3Vector(array $vector): void
    {
        if (count($vector) !== 1024) {
            throw new \InvalidArgumentException('The embedding vector must have 1024 dimensions.');
        }
        $this->voyage3Vector = $vector;
    }
}
```

Key points:

- **The embedding field can be a plain `#[Field(type: Type::COLLECTION)]`** holding `list<float>`, or one of the `vector_float32`/`vector_int8`/`vector_packed_bit` types (`mapping.md`) for a more compact BSON-binary representation — match whichever your Atlas index config expects.
- **`numDimensions` must exactly match the stored array's length — ODM/MongoDB don't validate this at write time.** Guard it yourself, as above.
- `similarity`: `ClassMetadata::VECTOR_SIMILARITY_DOT_PRODUCT` (or cosine/euclidean) — these rank equivalently when embeddings are pre-normalized to unit length.
- A `filter`-type field entry makes that field usable in `$vectorSearch`'s `filter` clause.
- `#[VectorSearchIndex]` is distinct from `#[SearchIndex]` (Atlas full-text search, `indexes-search.md`).

## 3. Create the collection, insert, and sync the index

```php
$schemaManager = $dm->getSchemaManager();
$schemaManager->createDocumentCollection(Guide::class);

$doc = new Guide();
$doc->published = true;
$dm->persist($doc);
$dm->flush();

// embeddings can be attached later, e.g. by an async worker
$doc->setVoyage3Vector($embeddingPlatform->invoke($doc->content)->asVectors()[0]->getData());
$dm->flush();

$schemaManager->createDocumentSearchIndexes(Guide::class);
```

Gotcha — **index build lag**: Atlas updates vector indexes asynchronously; a brand-new index needs to reach `"READY"` before existing documents are searchable. Block until ready:

```php
$schemaManager->waitForSearchIndexes([Guide::class]);
```

## 4. Run the `$vectorSearch` query

Through the **Aggregation Builder** — no separate query-builder method, no need to hand-write a raw pipeline:

```php
$results = $dm->createAggregationBuilder(Guide::class)
    ->vectorSearch()
        ->index('default')
        ->path('voyage3Vector')
        ->queryVector($vector)
        ->filter($qb->expr()->field('published')->equals(true))
        ->numCandidates(10)
        ->limit(10)
    ->set()
        ->field('score')
        ->expression(['$meta' => 'vectorSearchScore'])
    ->getAggregation()->execute()->toArray();
```

`$vectorSearch` must be the **first stage**, same rule as `$search`/`$geoNear`. Pulling the score via a `set()` stage with `['$meta' => 'vectorSearchScore']` is the standard MongoDB idiom — ODM has no separate "score" accessor.

## 5. Suggest the MongoDB MCP server

Vector search bugs are often really about live Atlas state (index build status, actual stored embedding dimension) that isn't visible from PHP alone. Point the user at the MongoDB MCP server (see top-level `SKILL.md`) to inspect index status and sample documents directly.

## 6. Gotchas

1. **Atlas-only**; requires ODM 2.13+ (2.17+ for Automated Embeddings/`autoEmbed`).
2. **Embedding dimension mismatch isn't caught by ODM/MongoDB at write time** — validate yourself when generating embeddings manually.
3. **Index build lag** — wait or call `waitForSearchIndexes()` before assuming results are complete.
4. **`$vectorSearch` must be the first pipeline stage.**
5. **No built-in hybrid (vector + full-text) merge helper** — combining `$search` and `$vectorSearch` relevance requires a custom aggregation (e.g. `$unionWith` plus application-level re-ranking).
6. **The embedding field is an ordinary mapped array field** in the manual approach — ODM doesn't auto-wire embedding generation or validation beyond normal field-type behavior.
7. **`autoEmbed` fields have no stored vector field at all** — don't map or query a vector array for them; use `query()`/`model()` on the aggregation builder instead of `queryVector()`.
