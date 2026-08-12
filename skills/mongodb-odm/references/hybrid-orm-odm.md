# Combining Doctrine ORM and MongoDB ODM (Hybrid Persistence)

## 1. Why / when

Running Doctrine ORM (SQL) and MongoDB ODM side by side, e.g. an `Order` in MySQL referencing a `Product` in MongoDB. There's **no built-in cross-persistence-layer relationship** — it's supported only indirectly, by wiring the two together with lifecycle events. Both `$em` and `$dm` are built completely independently; only the bridge is custom code.

## 2. Pattern A — reference an ODM document from an ORM entity by id

`Product` lives in MongoDB with a plain `#[Document]`/`#[Id]`/`#[Field]` mapping. `Order` lives in SQL and stores the product's id as a plain column, plus an **unmapped** in-memory property populated by an event listener:

```php
#[Entity]
#[Table(name: 'orders')]
class Order
{
    #[Column(type: 'string')]
    private string $productId;

    private Product $product; // NOT an ORM-mapped property

    public function setProduct(Product $product): void
    {
        $this->productId = $product->id;
        $this->product = $product;
    }
}
```

The bridge is a `postLoad` listener on the **ORM**'s `EventManager`, holding a `DocumentManager`, using reflection to set the private property:

```php
class MyEventSubscriber
{
    public function __construct(private readonly DocumentManager $dm) {}

    public function postLoad(LifecycleEventArgs $eventArgs): void
    {
        $order = $eventArgs->getEntity();

        if (!$order instanceof Order) {
            return; // postLoad fires for EVERY entity ORM loads
        }

        $product = $this->dm->getReference(Product::class, $order->getProductId());

        $eventArgs->getObjectManager()->getClassMetadata(Order::class)
            ->reflClass->getProperty('product')->setValue($order, $product);
    }
}

$em->getEventManager()->addEventListener([\Doctrine\ORM\Events::postLoad], new MyEventSubscriber($dm));
```

`getReference()` returns a lazy proxy that only hits MongoDB when a field is actually read.

## 3. Pattern B — one class, two independent mappings

A different technique: map the **same class** so it can persist independently to both a SQL table and a Mongo collection. **Not** synchronized dual-write — each mapping and each persist/flush is completely independent. If you persist the same class through both `$em` and `$dm`, you get two separate in-memory instances, since the ORM and ODM are separate libraries that don't share an object manager.

```php
// ORM mapping
#[ORM\Entity(repositoryClass: BlogPostRepository::class)]
class BlogPost
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[ORM\Column(type: 'string')]
    public string $title;
}

// ODM mapping — separate mapping source, same class name/namespace
#[ODM\Document(repositoryClass: BlogPostRepository::class)]
class BlogPost
{
    #[ODM\Id(type: 'int', strategy: 'INCREMENT')] // matches the ORM's integer id format
    public int $id;

    #[ODM\Field]
    public string $title;
}
```

Important: PHP attributes are compile-time bound to one class declaration — you can't literally stack both attribute sets on one file the way the two snippets above might imply. This dual-mapping pattern needs **XML mapping** (one XML file per layer, both pointing at the same class), not combined attributes on one file.

A shared repository interface can be implemented separately by an `EntityRepository` subclass and a `DocumentRepository` subclass, so calling code doesn't need to know which backend it's talking to.

## 4. What's supported vs. not

**Supported**: a scalar id column plus an unmapped, event-populated property (Pattern A); the same class mapped and persisted independently through both managers (Pattern B).

**Not supported**: there is **no native attribute** (no `#[ORM\ReferenceOne]` or ODM equivalent) that lets a mapping directly cross the ORM/ODM boundary and auto-hydrate. Every cross-layer link is hand-built via a scalar id plus an event listener resolving it through `getReference()` and reflection. Don't write `#[ODM\ReferenceOne(targetDocument: SomeOrmEntity::class)]` expecting it to work.

## 5. Gotchas

- Cross-layer linking only happens indirectly, through lifecycle events — there's no direct mapping-level link.
- `postLoad` fires for **every** entity the ORM loads — always `instanceof`-check.
- The bridge sets the property via **reflection**, bypassing ORM hydration, since it's intentionally unmapped.
- Mapping one class to both layers produces **two independent instances**, never synchronized.
- `INCREMENT` on the ODM side is only for id-format compatibility with an ORM integer id — not a default recommendation.
