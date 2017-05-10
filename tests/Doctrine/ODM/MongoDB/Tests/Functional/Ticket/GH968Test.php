<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH968Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testRefreshHintUpdatesEmbeddedNotReplaces()
    {
        $doc = new GH968Document('test');
        $embed = new GH968Embed('embedded');
        $doc->embed = $embed;

        $this->dm->persist($doc);
        $this->dm->flush();

        $refreshed = $this->dm->createQueryBuilder(get_class($doc))
                ->find()
                ->refresh()
                ->getQuery()
                ->getSingleResult();
        
        $this->assertSame($doc, $refreshed);
        $this->assertSame($embed, $refreshed->embed);
    }
}

/**
 * @ODM\Document
 */
class GH968Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;
    
    /** @ODM\EmbedOne(targetDocument="GH968Embed") */
    public $embed;
    
    public function __construct($name) 
    {
        $this->name = $name;
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class GH968Embed
{
    public $name;
    
    public function __construct($name)
    {
        $this->name = $name;
    }
}
