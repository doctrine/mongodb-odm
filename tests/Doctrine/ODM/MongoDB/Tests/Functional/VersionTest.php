<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class VersionTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testVersioningWhenManipulatingEmbedMany()
    {
        $doc = new VersionedDocument();
        $doc->name = 'test';
        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 1');
        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 2');
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->assertEquals(1, $doc->version);

        $doc->embedMany[] = new VersionedEmbeddedDocument('embed 3');
        $this->dm->flush();
        $this->assertEquals(2, $doc->version);
        
        unset($doc->embedMany[1]);
        $this->dm->flush();
        $this->assertEquals(3, $doc->version);

        $doc->embedMany->clear();
        $this->dm->flush();
        $this->assertEquals(4, $doc->version); 
        
        $doc->embedMany = null;
        $this->dm->flush();
        $this->assertEquals(5, $doc->version); 
    }
}

/**
 * @ODM\Document
 */
class VersionedDocument
{
    /** @ODM\Id */
    public $id;
    
    /** @ODM\Int @ODM\Version */
    public $version = 1;
    
    /** @ODM\String */
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
    public $value;
    
    public function __construct($value) 
    {
        $this->value = $value;
    }
}
