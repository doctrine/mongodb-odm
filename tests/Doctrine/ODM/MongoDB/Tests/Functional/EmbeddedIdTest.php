<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

class EmbeddedIdTest extends BaseTest
{
    public function testEmbeddedIdsAreGenerated()
    {
        $test = new DefaultIdEmbeddedDocument();

        $this->dm->persist($test);

        $this->assertNotNull($test->id);
    }

    public function testEmbeddedIdsAreNotOverwritten()
    {
        $id = (string) new ObjectId();
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

    /** @ODM\EmbedOne(targetDocument=DefaultIdEmbeddedDocument::class) */
    public $embedOne;

    /** @ODM\EmbedMany(targetDocument=DefaultIdEmbeddedDocument::class) */
    public $embedMany = [];
}

/** @ODM\Document */
class EmbeddedStrategyNoneIdTestUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument=DefaultIdStrategyNoneEmbeddedDocument::class) */
    public $embedOne;

    /** @ODM\EmbedMany(targetDocument=DefaultIdStrategyNoneEmbeddedDocument::class) */
    public $embedMany = [];
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
