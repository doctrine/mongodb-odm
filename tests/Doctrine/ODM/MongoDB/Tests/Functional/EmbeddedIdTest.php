<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoId;

class EmbeddedIdTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testEmbeddedIdsAreGenerated()
    {
        $test = new DefaultIdEmbeddedDocument();

        $this->dm->persist($test);

        $this->assertNotNull($test->id);
    }

    public function testEmbeddedIdsAreNotOverwritten()
    {
        $id = (string) new MongoId();
        $test = new DefaultIdEmbeddedDocument();
        $test->id = $id;

        $this->dm->persist($test);

        $this->assertEquals($id, $test->id);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Doctrine\ODM\MongoDB\Tests\Functional\DefaultIdStrategyNoneEmbeddedDocument uses NONE identifier generation strategy but no identifier was provided when persisting.
     */
    public function testEmbedOneDocumentWithMissingIdentifier()
    {
        $user = new EmbeddedStrategyNoneIdTestUser();
        $user->embedOne = new DefaultIdStrategyNoneEmbeddedDocument();

        $this->dm->persist($user);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Doctrine\ODM\MongoDB\Tests\Functional\DefaultIdStrategyNoneEmbeddedDocument uses NONE identifier generation strategy but no identifier was provided when persisting.
     */
    public function testEmbedManyDocumentWithMissingIdentifier()
    {
        $user = new EmbeddedStrategyNoneIdTestUser();
        $user->embedMany[] = new DefaultIdStrategyNoneEmbeddedDocument();

        $this->dm->persist($user);
    }
}

/** @ODM\Document */
class EmbeddedIdTestUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="DefaultIdEmbeddedDocument") */
    public $embedOne;

    /** @ODM\EmbedMany(targetDocument="DefaultIdEmbeddedDocument") */
    public $embedMany = array();
}

/** @ODM\Document */
class EmbeddedStrategyNoneIdTestUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="DefaultIdStrategyNoneEmbeddedDocument") */
    public $embedOne;

    /** @ODM\EmbedMany(targetDocument="DefaultIdStrategyNoneEmbeddedDocument") */
    public $embedMany = array();
}

/** @ODM\EmbeddedDocument */
class DefaultIdEmbeddedDocument
{
    /** @ODM\Id */
    public $id;
}

/** @ODM\EmbeddedDocument */
class DefaultIdStrategyNoneEmbeddedDocument
{
    /** @ODM\Id(strategy="none") */
    public $id;
}
