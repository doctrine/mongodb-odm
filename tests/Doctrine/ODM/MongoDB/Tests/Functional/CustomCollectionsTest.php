<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Tests\ClassMetadataTestUtil;
use Documents\File;
use Documents\ProfileNotify;
use stdClass;

use function assert;
use function get_class;

class CustomCollectionsTest extends BaseTest
{
    public function testMappingNamespaceIsAdded(): void
    {
        $d  = new DocumentWithCustomCollection();
        $cm = $this->dm->getClassMetadata(get_class($d));
        self::assertSame(MyEmbedsCollection::class, $cm->fieldMappings['coll']['collectionClass']);
    }

    public function testLeadingBackslashIsRemoved(): void
    {
        $d  = new DocumentWithCustomCollection();
        $cm = $this->dm->getClassMetadata(get_class($d));
        self::assertSame(MyDocumentsCollection::class, $cm->fieldMappings['inverseRefMany']['collectionClass']);
    }

    public function testCollectionClassHasToImplementCommonInterface(): void
    {
        $cm = new ClassMetadata('stdClass');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'stdClass used as custom collection class for stdClass::assoc has to implement ' .
            'Doctrine\Common\Collections\Collection interface.',
        );
        $cm->mapField([
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'many',
            'collectionClass' => stdClass::class,
        ]);
    }

    public function testGeneratedClassExtendsBaseCollection(): void
    {
        $coll  = new MyEmbedsCollection();
        $pcoll = $this->dm->getConfiguration()->getPersistentCollectionFactory()->create(
            $this->dm,
            ClassMetadataTestUtil::getFieldMapping(['collectionClass' => MyEmbedsCollection::class]),
            $coll,
        );
        self::assertInstanceOf(PersistentCollectionInterface::class, $pcoll);
        self::assertInstanceOf(MyEmbedsCollection::class, $pcoll);
        self::assertSame($coll, $pcoll->unwrap());
    }

    public function testEmbedMany(): void
    {
        $d = new DocumentWithCustomCollection();
        $d->coll->add(new EmbeddedDocumentInCustomCollection('#1', true));
        $d->coll->add(new EmbeddedDocumentInCustomCollection('#2', false));
        $d->boring->add(new EmbeddedDocumentInCustomCollection('#1', true));
        $d->boring->add(new EmbeddedDocumentInCustomCollection('#2', false));
        $this->dm->persist($d);
        $this->dm->flush();

        self::assertNotInstanceOf(PersistentCollection::class, $d->coll);
        self::assertInstanceOf(MyEmbedsCollection::class, $d->coll);
        self::assertInstanceOf(PersistentCollection::class, $d->boring);

        $this->dm->clear();
        $d = $this->dm->find(get_class($d), $d->id);

        self::assertNotInstanceOf(PersistentCollection::class, $d->coll);
        self::assertInstanceOf(MyEmbedsCollection::class, $d->coll);
        self::assertCount(1, $d->coll->getEnabled());
        self::assertCount(1, $d->coll->getByName('#1'));

        self::assertInstanceOf(PersistentCollection::class, $d->boring);
        self::assertCount(2, $d->boring);
    }

    public function testReferenceMany(): void
    {
        $d = new DocumentWithCustomCollection();
        $d->coll->add(new EmbeddedDocumentInCustomCollection('#1', true));
        $this->dm->persist($d);
        $d2 = new DocumentWithCustomCollection();
        $d2->refMany->add($d);
        $this->dm->persist($d2);
        $d3 = new DocumentWithCustomCollection();
        $this->dm->persist($d3);
        $d2->refMany->add($d3);
        $this->dm->flush();
        $this->dm->clear();

        $d2 = $this->dm->find(get_class($d2), $d2->id);
        self::assertNotInstanceOf(PersistentCollection::class, $d2->refMany);
        self::assertInstanceOf(MyDocumentsCollection::class, $d2->refMany);
        self::assertCount(2, $d2->refMany);
        self::assertCount(1, $d2->refMany->havingEmbeds());
    }

    public function testInverseSideOfReferenceMany(): void
    {
        $d = new DocumentWithCustomCollection();
        $this->dm->persist($d);
        $d2 = new DocumentWithCustomCollection();
        $d2->refMany->add($d);
        $this->dm->persist($d2);

        $this->dm->flush();
        $this->dm->clear();

        $d2 = $this->dm->find(get_class($d2), $d2->id);
        self::assertNotInstanceOf(PersistentCollection::class, $d2->inverseRefMany);
        self::assertInstanceOf(MyDocumentsCollection::class, $d2->inverseRefMany);
    }

    public function testModifyingCollectionByCustomMethod(): void
    {
        $d = new DocumentWithCustomCollection();
        $d->coll->add($e1 = new EmbeddedDocumentInCustomCollection('#1', true));
        $d->coll->add($e2 = new EmbeddedDocumentInCustomCollection('#2', false));
        $this->dm->persist($d);
        $this->dm->flush();

        $d = $this->dm->find(get_class($d), $d->id);
        $d->coll->move(0, 1);
        $this->dm->flush();
        $this->dm->clear();

        $d = $this->dm->find(get_class($d), $d->id);
        self::assertCount(2, $d->coll);
        self::assertEquals($e2, $d->coll[0]);
        self::assertEquals($e1, $d->coll[1]);
    }

    public function testModifyingCollectionInChangeTrackingNotifyDocument(): void
    {
        $repository = $this->dm->getRepository(File::class);
        assert($repository instanceof GridFSRepository);

        $f1 = $repository->uploadFromFile(__FILE__);
        $f2 = $repository->uploadFromFile(__FILE__);

        $profile = new ProfileNotify();
        $profile->getImages()->add($f1);
        $profile->getImages()->add($f2);
        $this->dm->persist($profile);
        $this->dm->flush();

        $profile = $this->dm->find(get_class($profile), $profile->getProfileId());
        $profile->getImages()->move(0, 1);
        $this->dm->flush();
        $this->dm->clear();

        $profile = $this->dm->find(get_class($profile), $profile->getProfileId());
        self::assertCount(2, $profile->getImages());
        self::assertEquals($f2->getId(), $profile->getImages()[0]->getId());
        self::assertEquals($f1->getId(), $profile->getImages()[1]->getId());
    }

    public function testMethodWithVoidReturnType(): void
    {
        $d = new DocumentWithCustomCollection();
        $this->dm->persist($d);
        $this->dm->flush();

        $d = $this->dm->find(get_class($d), $d->id);
        self::assertInstanceOf(MyEmbedsCollection::class, $d->coll);
        $d->coll->nothingReally();
    }
}

/** @ODM\Document */
class DocumentWithCustomCollection
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedMany(
     *   collectionClass=MyEmbedsCollection::class,
     *   targetDocument=EmbeddedDocumentInCustomCollection::class
     * )
     *
     * @var MyEmbedsCollection<int, EmbeddedDocumentInCustomCollection>
     */
    public $coll;

    /**
     * @ODM\EmbedMany(
     *   targetDocument=EmbeddedDocumentInCustomCollection::class
     * )
     *
     * @var Collection<int, EmbeddedDocumentInCustomCollection>
     */
    public $boring;

    /**
     * @ODM\ReferenceMany(
     *   collectionClass=MyDocumentsCollection::class,
     *   orphanRemoval=true,
     *   targetDocument=DocumentWithCustomCollection::class
     * )
     *
     * @var MyDocumentsCollection<int, DocumentWithCustomCollection>
     */
    public $refMany;

    /**
     * @ODM\ReferenceMany(
     *   collectionClass="\Doctrine\ODM\MongoDB\Tests\Functional\MyDocumentsCollection",
     *   mappedBy="refMany",
     *   targetDocument=DocumentWithCustomCollection::class
     * )
     *
     * @var MyDocumentsCollection<int, DocumentWithCustomCollection>
     */
    public $inverseRefMany;

    public function __construct()
    {
        $this->boring         = new ArrayCollection();
        $this->coll           = new MyEmbedsCollection();
        $this->refMany        = new MyDocumentsCollection();
        $this->inverseRefMany = new MyDocumentsCollection();
    }
}

/** @ODM\EmbeddedDocument */
class EmbeddedDocumentInCustomCollection
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\Field(type="bool")
     *
     * @var bool
     */
    public $enabled;

    public function __construct(string $name, bool $enabled)
    {
        $this->name    = $name;
        $this->enabled = $enabled;
    }
}

/**
 * @template TKey of array-key
 * @template TElement
 * @template-extends ArrayCollection<TKey, TElement>
 */
class MyEmbedsCollection extends ArrayCollection
{
    /** @return MyEmbedsCollection<TKey, TElement> */
    public function getByName(string $name): MyEmbedsCollection
    {
        return $this->filter(static fn ($item) => $item->name === $name);
    }

    /** @return MyEmbedsCollection<TKey, TElement> */
    public function getEnabled(): MyEmbedsCollection
    {
        return $this->filter(static fn ($item) => $item->enabled);
    }

    public function move(int $i, int $j): void
    {
        $tmp = $this->get($i);
        $this->set($i, $this->get($j));
        $this->set($j, $tmp);
    }

    public function nothingReally(): void
    {
    }
}

/**
 * @template TKey of array-key
 * @template TElement
 * @template-extends ArrayCollection<TKey, TElement>
 */
class MyDocumentsCollection extends ArrayCollection
{
    /** @return MyDocumentsCollection<TKey, TElement> */
    public function havingEmbeds(): MyDocumentsCollection
    {
        return $this->filter(static fn ($item) => $item->coll->count());
    }
}
