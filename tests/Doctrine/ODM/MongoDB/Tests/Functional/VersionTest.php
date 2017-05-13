<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class VersionTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testVersioningWhenManipulatingEmbedMany()
    {
        $expectedVersion = 1;
        $doc = new VersionedDocument();
        $doc->name = 'test';
        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 1');
        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 2');
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 3');
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);
        
        $doc->embedMany[0]->embedMany[] = new VersionedEmbeddedDocument('deeply embed 1');
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);
        
        unset($doc->embedMany[1]);
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);

        $doc->embedMany->clear();
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version); 
        
        $doc->embedMany = null;
        $this->dm->flush();
        $this->assertEquals($expectedVersion++, $doc->version);
    }
}

/**
 * @ODM\Document
 */
class VersionedDocument
{
    /** @ODM\Id */
    public $id;
    
    /** @ODM\Field(type="int", name="_version") @ODM\Version */
    public $version = 1;
    
    /** @ODM\Field(type="string") */
    public $name;
    
    /** @ODM\EmbedMany(targetDocument="VersionedEmbeddedDocument") */
    public $embedMany = array();
    
    public function __construct()
    {
        $this->embedMany = new \Doctrine\Common\Collections\ArrayCollection();
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class VersionedEmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $value;
    
    /** @ODM\EmbedMany(targetDocument="VersionedEmbeddedDocument") */
    public $embedMany;
    
    public function __construct($value) 
    {
        $this->value = $value;
        $this->embedMany = new \Doctrine\Common\Collections\ArrayCollection();
    }
}
