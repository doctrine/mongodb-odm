<?php
namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Github Doctrine MongoDB ODM test case.
 *
 * When an embedded document is persisted and flushed, and then the parent
 * document is persisted and flushed, the parent document does not correctly
 * have the contents of the embedded document persisted.
 *
 * @author Sean Quinn <swquinn@gmail.com>
 * @since 2/16/2013
 */
class GH501Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testPersistedAndFlushedEmbeddedDocumentInsideDocumentWithId()
    {
        /* PARENT DOCUMENT */
        $doc = new GH501Document('AlbumDocId');
		$doc->id = new \MongoId();
        /* END PARENT DOCUMENT */
		
		/* EMBEDDED DISCRIMINATED NODE */
		$node = new GH501Album("TestAlbum", 1);
		$doc->node = $node;
		/* END EMBEDDED DISCRIMINATED NODE */

		// Persist and flush the node.
		$this->dm->persist($node);
		$this->dm->flush();

		// Now, persist and flush the object you would expect to be operating on.
        $this->dm->persist($doc);
        $this->dm->flush();

		$doc = $this->dm->getRepository(__NAMESPACE__ . '\GH501Document')->findOneByName('AlbumDocId');
		$this->assertEquals('AlbumDocId', $doc->name);
		$this->assertEquals('TestAlbum', $doc->node->name);
		$this->assertEquals('album', $doc->node->type);
		$this->assertEquals(1, $doc->node->value);
    }
	
    public function testPersistedAndFlushedEmbeddedDocumentInsideDocumentWithoutId()
    {
        /* PARENT DOCUMENT */
        $doc = new GH501Document('AlbumDocNoId');
        /* END PARENT DOCUMENT */
		
		/* EMBEDDED DISCRIMINATED NODE */
		$node = new GH501Album("TestAlbum", 1);
		$doc->node = $node;
		/* END EMBEDDED DISCRIMINATED NODE */

		// Persist and flush the node.
		$this->dm->persist($node);
		$this->dm->flush();

		// Now, persist and flush the object you would expect to be operating on.
        $this->dm->persist($doc);
        $this->dm->flush();

		$doc = $this->dm->getRepository(__NAMESPACE__ . '\GH501Document')->findOneByName('AlbumDocNoId');
		$this->assertEquals('AlbumDocNoId', $doc->name);
		$this->assertEquals('TestAlbum', $doc->node->name);
		$this->assertEquals('album', $doc->node->type);
		$this->assertEquals(1, $doc->node->value);
    }
	
    public function testOnlyPersisTheEncompassingDocument()
    {
        /* PARENT DOCUMENT */
        $doc = new GH501Document('ArticleDoc');
        /* END PARENT DOCUMENT */
		
		/* EMBEDDED DISCRIMINATED NODE */
		$node = new GH501Article("TestArticle", 37);
		$doc->node = $node;
		/* END EMBEDDED DISCRIMINATED NODE */

		// Now, persist and flush the object you would expect to be operating on.
        $this->dm->persist($doc);
        $this->dm->flush();
		$this->dm->clear();

		$doc = $this->dm->getRepository(__NAMESPACE__ . '\GH501Document')->findOneByName('ArticleDoc');
		$this->assertEquals('ArticleDoc', $doc->name);
		$this->assertEquals('TestArticle', $doc->node->name);
		$this->assertEquals('article', $doc->node->type);
		$this->assertEquals(37, $doc->node->value);
    }
}

/** @ODM\Document */
class GH501Document
{
    /** @ODM\Id */
    public $id;
	
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
	/** @ODM\String */
	public $name;
	
	/** @ODM\Int */
	public $value;

	public $type = 'album';

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
	/** @ODM\String */
	public $name;
	
	/** @ODM\Int */
	public $value;

	public $type = 'article';
	
	public function __construct($name, $value)
	{
		parent::__construct($name, $value);
		$this->type = 'article';
	}
}
