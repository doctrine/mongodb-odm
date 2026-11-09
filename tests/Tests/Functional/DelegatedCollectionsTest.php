<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Countable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Mapping\PropertyAccessors\DelegateCollectionPropertyAccessor;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\AbstractDelegateCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\DelegateCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\DelegateCollectionFactory;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use IteratorAggregate;
use Traversable;

use function assert;
use function serialize;
use function sort;
use function unserialize;

class DelegatedCollectionsTest extends BaseTestCase
{
    protected static function getConfiguration(): Configuration
    {
        $config = parent::getConfiguration();
        $config->setDelegateCollectionFactory(new DelegatedCollectionFactoryForTests());

        return $config;
    }

    public function testEmbedManyUsesDelegatedCollection(): void
    {
        $document = new DelegatedDocument();
        $document->names->addName(new EmbeddedName('b'));
        $document->names->addName(new EmbeddedName('a'));

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $loaded = $this->dm->find(DelegatedDocument::class, $document->id);
        assert($loaded instanceof DelegatedDocument);

        self::assertInstanceOf(DelegatedNameList::class, $loaded->names);
        self::assertNotInstanceOf(PersistentCollection::class, $loaded->names);
        self::assertSame(['a', 'b'], $loaded->names->names());
        self::assertInstanceOf(PersistentCollectionInterface::class, $loaded->names->getDelegatee());
    }

    public function testFullApiCollectionReflectsTheInnerInitializationState(): void
    {
        $document = new DelegatedDocument();
        $document->fullNames->addName(new EmbeddedName('php'));

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $loaded = $this->dm->find(DelegatedDocument::class, $document->id);
        assert($loaded instanceof DelegatedDocument);

        self::assertInstanceOf(DelegatedFullApiNameList::class, $loaded->fullNames);

        $initializedBefore = $loaded->fullNames->isInitialized();
        self::assertFalse($initializedBefore);
        self::assertSame(['php'], $loaded->fullNames->names());

        $initializedAfter = $loaded->fullNames->isInitialized();
        self::assertTrue($initializedAfter);
    }

    public function testReferenceManyUsesDelegatedCollection(): void
    {
        $child = new DelegatedDocument();
        $this->dm->persist($child);

        $parent = new DelegatedDocument();
        $parent->children->addDocument($child);
        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $loaded = $this->dm->find(DelegatedDocument::class, $parent->id);
        assert($loaded instanceof DelegatedDocument);

        self::assertInstanceOf(DelegatedDocumentList::class, $loaded->children);
        self::assertCount(1, $loaded->children);
        self::assertInstanceOf(PersistentCollectionInterface::class, $loaded->children->getDelegatee());

        $loaded->children->removeDocument($loaded->children->firstDocument());
        $this->dm->flush();

        self::assertNull($this->dm->find(DelegatedDocument::class, $child->id));
    }

    public function testInverseSideOfReferenceManyUsesDelegatedCollection(): void
    {
        $child = new DelegatedDocument();
        $this->dm->persist($child);

        $parent = new DelegatedDocument();
        $parent->children->addDocument($child);
        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $loadedChild = $this->dm->find(DelegatedDocument::class, $child->id);
        assert($loadedChild instanceof DelegatedDocument);

        self::assertInstanceOf(DelegatedDocumentList::class, $loadedChild->parents);
        self::assertCount(1, $loadedChild->parents);
    }

    public function testConstructorDependencyIsInjectedByFactory(): void
    {
        $document = new DelegatedDocument();
        $document->brandedNames->addName(new EmbeddedName('php'));

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $loaded = $this->dm->find(DelegatedDocument::class, $document->id);
        assert($loaded instanceof DelegatedDocument);

        self::assertInstanceOf(BrandedNameList::class, $loaded->brandedNames);
        self::assertSame(['brand:php'], $loaded->brandedNames->names());
    }

    public function testUserConstructedInstanceSurvivesFlush(): void
    {
        $document = new DelegatedDocument();
        $names    = $document->names;
        $names->addName(new EmbeddedName('php'));

        $this->dm->persist($document);
        $this->dm->flush();

        self::assertSame($names, $document->names);
        self::assertInstanceOf(PersistentCollectionInterface::class, $names->getDelegatee());
    }

    public function testCollectionIsClonedWithTheDocument(): void
    {
        $document = new DelegatedDocument();
        $document->names->addName(new EmbeddedName('php'));

        $clone = clone $document->names;
        $clone->addName(new EmbeddedName('mongodb'));

        self::assertSame(['php'], $document->names->names());
        self::assertSame(['mongodb', 'php'], $clone->names());
    }

    public function testPersistentCollectionFactoryReturnsPlainCollectionForDelegatedClass(): void
    {
        $inner = new ArrayCollection();
        /** @phpstan-var array<string, mixed> $mapping */
        $mapping = ['collectionClass' => DelegatedNameList::class];

        $collection = $this->dm->getConfiguration()->getPersistentCollectionFactory()->create(
            $this->dm,
            $mapping,
            $inner,
        );

        self::assertInstanceOf(PersistentCollection::class, $collection);
        self::assertSame($inner, $collection->unwrap());
    }

    public function testGetFieldValueReturnsTheUnderlyingCollection(): void
    {
        $document = new DelegatedDocument();
        $document->names->addName(new EmbeddedName('php'));

        $class = $this->dm->getClassMetadata(DelegatedDocument::class);
        $value = $class->getFieldValue($document, 'names');

        self::assertInstanceOf(Collection::class, $value);
        self::assertNotInstanceOf(DelegatedNameList::class, $value);
    }

    public function testMetadataSerializationKeepsTheDelegatedAccessor(): void
    {
        $class = $this->dm->getClassMetadata(DelegatedDocument::class);

        $restored = unserialize(serialize($class));
        assert($restored instanceof ClassMetadata);

        self::assertInstanceOf(DelegateCollectionPropertyAccessor::class, $restored->propertyAccessors['names']);
    }

    public function testDelegatedCollectionMustDeclareACollectionProperty(): void
    {
        $class = new ClassMetadata('stdClass');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('exactly one property typed as Doctrine\Common\Collections\Collection');

        $class->mapField([
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'many',
            'collectionClass' => CollectionWithoutProperty::class,
        ]);
    }

    public function testDelegatedCollectionMustDeclareExactlyOneCollectionProperty(): void
    {
        $class = new ClassMetadata('stdClass');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('exactly one property typed as Doctrine\Common\Collections\Collection');

        $class->mapField([
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'many',
            'collectionClass' => CollectionWithTwoProperties::class,
        ]);
    }

    public function testDelegatedCollectionPropertyMustNotBeReadonly(): void
    {
        $class = new ClassMetadata('stdClass');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('must not be readonly');

        $class->mapField([
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'many',
            'collectionClass' => CollectionWithReadonlyProperty::class,
        ]);
    }

    public function testDelegatedCollectionInterfaceMustBeReplacedByAConcreteClass(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('must be instantiable');

        $this->dm->getClassMetadata(InterfaceTypedDelegatedDocument::class);
    }
}

#[ODM\Document]
class DelegatedDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    #[ODM\EmbedMany(targetDocument: EmbeddedName::class)]
    public DelegatedNameList $names;

    #[ODM\ReferenceMany(targetDocument: self::class, orphanRemoval: true)]
    public DelegatedDocumentList $children;

    #[ODM\ReferenceMany(targetDocument: self::class, mappedBy: 'children')]
    public DelegatedDocumentList $parents;

    #[ODM\EmbedMany(targetDocument: EmbeddedName::class, collectionClass: BrandedNameList::class)]
    public BrandedNameList $brandedNames;

    #[ODM\EmbedMany(targetDocument: EmbeddedName::class)]
    public DelegatedFullApiNameList $fullNames;

    public function __construct()
    {
        $this->names        = new DelegatedNameList();
        $this->children     = new DelegatedDocumentList();
        $this->parents      = new DelegatedDocumentList();
        $this->brandedNames = new BrandedNameList('new:');
        $this->fullNames    = new DelegatedFullApiNameList();
    }
}

#[ODM\EmbeddedDocument]
class EmbeddedName
{
    #[ODM\Field(type: 'string')]
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/**
 * @template-implements IteratorAggregate<array-key, EmbeddedName>
 * @template-implements DelegateCollection<array-key, EmbeddedName>
 */
final class DelegatedNameList implements Countable, DelegateCollection, IteratorAggregate
{
    /** @var Collection<array-key, EmbeddedName> */
    private Collection $collection;

    /** @param Collection<array-key, EmbeddedName>|null $collection */
    public function __construct(Collection|null $collection = null)
    {
        $this->collection = $collection ?? new ArrayCollection();
    }

    /** @return Collection<array-key, EmbeddedName> */
    public function getDelegatee(): Collection
    {
        return $this->collection;
    }

    public function getIterator(): Traversable
    {
        return $this->collection->getIterator();
    }

    public function count(): int
    {
        return $this->collection->count();
    }

    public function addName(EmbeddedName $name): void
    {
        $this->collection->add($name);
    }

    /** @return list<string> */
    public function names(): array
    {
        $names = [];
        foreach ($this->collection as $name) {
            $names[] = $name->name;
        }

        sort($names);

        return $names;
    }

    public function __clone()
    {
        $this->collection = clone $this->collection;
    }
}

/** @template-extends AbstractDelegateCollection<array-key, EmbeddedName> */
final class DelegatedFullApiNameList extends AbstractDelegateCollection
{
    public function addName(EmbeddedName $name): void
    {
        $this->add($name);
    }

    /** @return list<string> */
    public function names(): array
    {
        $names = [];
        foreach ($this->toArray() as $name) {
            assert($name instanceof EmbeddedName);
            $names[] = $name->name;
        }

        sort($names);

        return $names;
    }
}

/**
 * A delegated collection whose constructor requires a dependency provided by
 * the configured DelegateCollectionFactory.
 *
 * @template-implements IteratorAggregate<array-key, EmbeddedName>
 * @template-implements DelegateCollection<array-key, EmbeddedName>
 */
final class BrandedNameList implements Countable, DelegateCollection, IteratorAggregate
{
    /** @var Collection<array-key, EmbeddedName> */
    private Collection $collection;

    /** @param Collection<array-key, EmbeddedName>|null $collection */
    public function __construct(private readonly string $prefix, Collection|null $collection = null)
    {
        $this->collection = $collection ?? new ArrayCollection();
    }

    public function getIterator(): Traversable
    {
        return $this->collection->getIterator();
    }

    public function count(): int
    {
        return $this->collection->count();
    }

    public function addName(EmbeddedName $name): void
    {
        $this->collection->add($name);
    }

    /** @return list<string> */
    public function names(): array
    {
        $names = [];
        foreach ($this->collection as $name) {
            $names[] = $this->prefix . $name->name;
        }

        sort($names);

        return $names;
    }
}

/**
 * @template-implements IteratorAggregate<array-key, DelegatedDocument>
 * @template-implements DelegateCollection<array-key, DelegatedDocument>
 */
final class DelegatedDocumentList implements Countable, DelegateCollection, IteratorAggregate
{
    /** @var Collection<array-key, DelegatedDocument> */
    private Collection $collection;

    /** @param Collection<array-key, DelegatedDocument>|null $collection */
    public function __construct(Collection|null $collection = null)
    {
        $this->collection = $collection ?? new ArrayCollection();
    }

    /** @return Collection<array-key, DelegatedDocument> */
    public function getDelegatee(): Collection
    {
        return $this->collection;
    }

    public function getIterator(): Traversable
    {
        return $this->collection->getIterator();
    }

    public function count(): int
    {
        return $this->collection->count();
    }

    public function addDocument(DelegatedDocument $document): void
    {
        $this->collection->add($document);
    }

    public function removeDocument(DelegatedDocument $document): void
    {
        $this->collection->removeElement($document);
    }

    public function firstDocument(): DelegatedDocument
    {
        $document = $this->collection->first();
        assert($document instanceof DelegatedDocument);

        return $document;
    }
}

final class DelegatedCollectionFactoryForTests implements DelegateCollectionFactory
{
    public function create(string $collectionClass): DelegateCollection
    {
        return match ($collectionClass) {
            BrandedNameList::class => new BrandedNameList('brand:'),
            default => new $collectionClass(),
        };
    }
}

/**
 * @template-implements IteratorAggregate<array-key, object>
 * @template-implements DelegateCollection<array-key, object>
 */
final class CollectionWithoutProperty implements DelegateCollection, IteratorAggregate
{
    /** @var list<object> */
    private array $items = [];

    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}

/**
 * @template-implements IteratorAggregate<array-key, object>
 * @template-implements DelegateCollection<array-key, object>
 */
final class CollectionWithTwoProperties implements DelegateCollection, IteratorAggregate
{
    /** @var Collection<array-key, object> */
    private Collection $first;
    /** @var Collection<array-key, object> */
    private Collection $second;

    public function __construct()
    {
        $this->first  = new ArrayCollection();
        $this->second = new ArrayCollection();
    }

    public function getIterator(): Traversable
    {
        return $this->first->getIterator();
    }
}

/**
 * @template-implements IteratorAggregate<array-key, object>
 * @template-implements DelegateCollection<array-key, object>
 */
final class CollectionWithReadonlyProperty implements DelegateCollection, IteratorAggregate
{
    /** @param Collection<array-key, object> $collection */
    public function __construct(private readonly Collection $collection)
    {
    }

    public function getIterator(): Traversable
    {
        return $this->collection->getIterator();
    }
}

/** @template-extends DelegateCollection<array-key, DelegatedDocument> */
interface DelegatedCollectionInterface extends DelegateCollection
{
}

#[ODM\Document]
class InterfaceTypedDelegatedDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    #[ODM\ReferenceMany(targetDocument: self::class)]
    public DelegatedCollectionInterface $children;
}
