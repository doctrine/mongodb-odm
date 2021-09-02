<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
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
        $this->assertSame(MyEmbedsCollection::class, $cm->fieldMappings['coll']['collectionClass']);
    }

    public function testLeadingBackslashIsRemoved(): void
    {
        $d  = new DocumentWithCustomCollection();
        $cm = $this->dm->getClassMetadata(get_class($d));
        $this->assertSame(MyDocumentsCollection::class, $cm->fieldMappings['inverseRefMany']['collectionClass']);
    }

    public function testCollectionClassHasToImplementCommonInterface(): void
    {
        $cm = new ClassMetadata('stdClass');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'stdClass used as custom collection class for stdClass::assoc has to implement ' .
            'Doctrine\Common\Collections\Collection interface.'
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
            ['collectionClass' => MyEmbedsCollection::class],
            $coll
        );
        $this->assertInstanceOf(PersistentCollectionInterface::class, $pcoll);
        $this->assertInstanceOf(MyEmbedsCollection::class, $pcoll);
        $this->assertSame($coll, $pcoll->unwrap());
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

        $this->assertNotInstanceOf(PersistentCollection::class, $d->coll);
        $this->assertInstanceOf(MyEmbedsCollection::class, $d->coll);
        $this->assertInstanceOf(PersistentCollection::class, $d->boring);

        $this->dm->clear();
        $d = $this->dm->find(get_class($d), $d->id);

        $this->assertNotInstanceOf(PersistentCollection::class, $d->coll);
        $this->assertInstanceOf(MyEmbedsCollection::class, $d->coll);
        $this->assertCount(1, $d->coll->getEnabled());
        $this->assertCount(1, $d->coll->getByName('#1'));

        $this->assertInstanceOf(PersistentCollection::class, $d->boring);
        $this->assertCount(2, $d->boring);
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
        $this->assertNotInstanceOf(PersistentCollection::class, $d2->refMany);
        $this->assertInstanceOf(MyDocumentsCollection::class, $d2->refMany);
        $this->assertCount(2, $d2->refMany);
        $this->assertCount(1, $d2->refMany->havingEmbeds());
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
        $this->assertNotInstanceOf(PersistentCollection::class, $d2->inverseRefMany);
        $this->assertInstanceOf(MyDocumentsCollection::class, $d2->inverseRefMany);
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
        $this->assertCount(2, $d->coll);
        $this->assertEquals($e2, $d->coll[0]);
        $this->assertEquals($e1, $d->coll[1]);
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
        $this->assertCount(2, $profile->getImages());
        $this->assertEquals($f2->getId(), $profile->getImages()[0]->getId());
        $this->assertEquals($f1->getId(), $profile->getImages()[1]->getId());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testMethodWithVoidReturnType(): void
    {
        $d = new DocumentWithCustomCollection();
        $this->dm->persist($d);
        $this->dm->flush();

        $d = $this->dm->find(get_class($d), $d->id);
        $d->coll->nothingReally();
    }
}

/**
 * @ODM\Document
 */
class DocumentWithCustomCollection
{
    /** @ODM\Id */
    public $id;

    /**
     * @ODM\EmbedMany(
     *   collectionClass=MyEmbedsCollection::class,
     *   targetDocument=EmbeddedDocumentInCustomCollection::class
     * )
     */
    public $coll;

    /**
     * @ODM\EmbedMany(
     *   targetDocument=EmbeddedDocumentInCustomCollection::class
     * )
     */
    public $boring;

    /**
     * @ODM\ReferenceMany(
     *   collectionClass=MyDocumentsCollection::class,
     *   orphanRemoval=true,
     *   targetDocument=DocumentWithCustomCollection::class
     * )
     */
    public $refMany;

    /**
     * @ODM\ReferenceMany(
     *   collectionClass="\Doctrine\ODM\MongoDB\Tests\Functional\MyDocumentsCollection",
     *   mappedBy="refMany",
     *   targetDocument=DocumentWithCustomCollection::class
     * )
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

/**
 * @ODM\EmbeddedDocument
 */
class EmbeddedDocumentInCustomCollection
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\Field(type="bool")
     *
     * @var bool|null
     */
    public $enabled;

    public function __construct($name, $enabled)
    {
        $this->name    = $name;
        $this->enabled = $enabled;
    }
}

class MyEmbedsCollection extends ArrayCollection
{
    public function getByName($name)
    {
        return $this->filter(static function ($item) use ($name) {
            return $item->name === $name;
        });
    }

    public function getEnabled()
    {
        return $this->filter(static function ($item) {
            return $item->enabled;
        });
    }

    public function move($i, $j): void
    {
        $tmp = $this->get($i);
        $this->set($i, $this->get($j));
        $this->set($j, $tmp);
    }

    public function nothingReally(): void
    {
    }
}

class MyDocumentsCollection extends ArrayCollection
{
    public function havingEmbeds()
    {
        return $this->filter(static function ($item) {
            return $item->coll->count();
        });
    }
}
