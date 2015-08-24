<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

class CustomCollectionsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testMappingNamespaceIsAdded()
    {
        $d = new DocumentWithCustomCollection();
        $cm = $this->dm->getClassMetadata(get_class($d));
        $this->assertSame('Doctrine\\ODM\\MongoDB\\Tests\\Functional\\MyEmbedsCollection', $cm->fieldMappings['coll']['collectionClass']);
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

    public function testFunctional()
    {
        $d = new DocumentWithCustomCollection();
        $d->coll->add(new EmbeddedDocumentInCustomCollection('#1', true));
        $d->coll->add(new EmbeddedDocumentInCustomCollection('#2', false));
        $d->boring->add(new EmbeddedDocumentInCustomCollection('#1', true));
        $d->boring->add(new EmbeddedDocumentInCustomCollection('#2', false));
        $this->dm->persist($d);
        $this->dm->flush();

        $this->assertNotInstanceOf('Doctrine\\ODM\\MongoDB\\PersistentCollection', $d->coll);
        $this->assertInstanceOf('Doctrine\\ODM\\MongoDB\\Tests\\Functional\\MyEmbedsCollection', $d->coll->unwrap());

        $this->dm->clear();
        $d = $this->dm->find(get_class($d), $d->id);

        $this->assertNotInstanceOf('Doctrine\\ODM\\MongoDB\\PersistentCollection', $d->coll);
        $this->assertInstanceOf('Doctrine\\ODM\\MongoDB\\Tests\\Functional\\MyEmbedsCollection', $d->coll->unwrap());
        $this->assertCount(1, $d->coll->getEnabled());
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

    public function __construct()
    {
        $this->boring = new ArrayCollection();
        $this->coll = new MyEmbedsCollection();
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class EmbeddedDocumentInCustomCollection
{
    /** @ODM\String */
    public $name;

    /** @ODM\Bool */
    public $enabled;

    public function __construct($name, $enabled)
    {
        $this->name = $name;
        $this->enabled = $enabled;
    }
}

class MyEmbedsCollection extends ArrayCollection
{
    public function getEnabled()
    {
        return $this->filter(function($item) {
            return $item->enabled;
        });
    }
}
