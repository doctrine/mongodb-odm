<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Documents\File;
use Documents\ProfileNotify;

class CustomCollectionsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testMappingNamespaceIsAdded()
    {
        $d = new DocumentWithCustomCollection();
        $cm = $this->dm->getClassMetadata(get_class($d));
        $this->assertSame(MyEmbedsCollection::class, $cm->fieldMappings['coll']['collectionClass']);
    }

    public function testLeadingBackslashIsRemoved()
    {
        $d = new DocumentWithCustomCollection();
        $cm = $this->dm->getClassMetadata(get_class($d));
        $this->assertSame(MyDocumentsCollection::class, $cm->fieldMappings['inverseRefMany']['collectionClass']);
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage stdClass used as custom collection class for stdClass::assoc has to implement Doctrine\Common\Collections\Collection interface.
     */
    public function testCollectionClassHasToImplementCommonInterface()
    {
        $cm = new ClassMetadataInfo('stdClass');

        $cm->mapField(array(
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'many',
            'collectionClass' => 'stdClass',
        ));
    }

    public function testGeneratedClassExtendsBaseCollection()
    {
        $coll = new MyEmbedsCollection();
        $pcoll = $this->dm->getConfiguration()->getPersistentCollectionFactory()->create(
            $this->dm,
            ['collectionClass' => MyEmbedsCollection::class],
            $coll
        );
        $this->assertInstanceOf(PersistentCollectionInterface::class, $pcoll);
        $this->assertInstanceOf(MyEmbedsCollection::class, $pcoll);
        $this->assertSame($coll, $pcoll->unwrap());
    }

    public function testEmbedMany()
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

    public function testReferenceMany()
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

    public function testInverseSideOfReferenceMany()
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

    public function testModifyingCollectionByCustomMethod()
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

    public function testModifyingCollectionInChangeTrackingNotifyDocument()
    {
        $profile = new ProfileNotify();
        $f1 = new File();
        $f1->setName('av.jpeg');
        $profile->getImages()->add($f1);
        $f2 = new File();
        $f2->setName('ghost.gif');
        $profile->getImages()->add($f2);
        $this->dm->persist($profile);
        $this->dm->flush();

        $profile = $this->dm->find(get_class($profile), $profile->getProfileId());
        $profile->getImages()->move(0, 1);
        $this->dm->flush();
        $this->dm->clear();

        $profile = $this->dm->find(get_class($profile), $profile->getProfileId());
        $this->assertCount(2, $profile->getImages());
        $this->assertEquals($f2->getName(), $profile->getImages()[0]->getName());
        $this->assertEquals($f1->getName(), $profile->getImages()[1]->getName());
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
     *   collectionClass="MyEmbedsCollection",
     *   targetDocument="EmbeddedDocumentInCustomCollection"
     * )
     */
    public $coll;

    /**
     * @ODM\EmbedMany(
     *   targetDocument="EmbeddedDocumentInCustomCollection"
     * )
     */
    public $boring;

    /**
     * @ODM\ReferenceMany(
     *   collectionClass="MyDocumentsCollection",
     *   orphanRemoval=true,
     *   targetDocument="DocumentWithCustomCollection"
     * )
     */
    public $refMany;

    /**
     * @ODM\ReferenceMany(
     *   collectionClass="\Doctrine\ODM\MongoDB\Tests\Functional\MyDocumentsCollection",
     *   mappedBy="refMany",
     *   targetDocument="DocumentWithCustomCollection"
     * )
     */
    public $inverseRefMany;

    public function __construct()
    {
        $this->boring = new ArrayCollection();
        $this->coll = new MyEmbedsCollection();
        $this->refMany = new MyDocumentsCollection();
        $this->inverseRefMany = new MyDocumentsCollection();
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class EmbeddedDocumentInCustomCollection
{
    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\Field(type="bool") */
    public $enabled;

    public function __construct($name, $enabled)
    {
        $this->name = $name;
        $this->enabled = $enabled;
    }
}

class MyEmbedsCollection extends ArrayCollection
{
    public function getByName($name)
    {
        return $this->filter(function($item) use ($name) {
            return $item->name === $name;
        });
    }

    public function getEnabled()
    {
        return $this->filter(function($item) {
            return $item->enabled;
        });
    }

    public function move($i, $j)
    {
        $tmp = $this->get($i);
        $this->set($i, $this->get($j));
        $this->set($j, $tmp);
    }
}

class MyDocumentsCollection extends ArrayCollection
{
    public function havingEmbeds()
    {
        return $this->filter(function($item) {
            return $item->coll->count();
        });
    }
}
