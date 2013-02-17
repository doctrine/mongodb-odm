<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @author Sean Quinn <swquinn@gmail.com>
 * @since 2/16/2013
 */
class GH501Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testReferencedDocumentInsideEmbeddedDocument()
    {
        /* PARENT DOCUMENT */
        $doc = new GH501Document('Test');
        /* END PARENT DOCUMENT */
		
		/* EMBEDDED DISCRIMINATED NODE */
		$nodeA = new GH501Album("TestNode", 1);
		$doc->node = $nodeA;
		/* END EMBEDDED DISCRIMINATED NODE */

        // persist & flush
        $this->dm->persist($doc);
        $this->dm->flush();
		
		// Before clear:
		$doc = $this->dm->getRepository(__NAMESPACE__ . '\GH501Document')->findOneByName('Test');
		$this->assertEquals('Test', $doc->name);
		$this->assertEquals('TestNode', $doc->node->name);
		$this->assertEquals('album', $doc->node->type);
		$this->assertEquals(1, $doc->node->value);

        $this->dm->clear();
		
		// After clear:
		$doc = $this->dm->getRepository(__NAMESPACE__ . '\GH501Document')->findOneByName('Test');
		$this->assertEquals('Test', $doc->name);
		$this->assertEquals('TestNode', $doc->node->name);
		$this->assertEquals('album', $doc->node->type);
		$this->assertEquals(1, $doc->node->value);
    }
}

/** @ODM\Document(db="gh501_test",collection="doc") */
class GH501Document
{
    /** @ODM\Id */
    protected $id;
	
	/** @ODM\String */
	public $name;

    /** @ODM\Hash */
    public $hash = array();

    /** @ODM\EmbedOne(name="node") */
    public $node;

    public function __construct($name)
    {
		$this->name = $name;
    }
}

abstract class GH501BaseNode
{
	public $name;
	
	public $type;

	public $value;
	
	public function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = $value;
	}
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"album"="GH501Album", "article"="GH501Article"})
 */
class GH501Album extends GH501BaseNode
{
	public function __construct($name, $value)
	{
		parent::__construct($name, $value);
		$this->type = 'album';
	}
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"album"="GH501Album", "article"="GH501Article"})
 */
class GH501Article extends GH501BaseNode
{
	public function __construct($name, $value)
	{
		parent::__construct($name, $value);
		$this->type = 'article';
	}
}