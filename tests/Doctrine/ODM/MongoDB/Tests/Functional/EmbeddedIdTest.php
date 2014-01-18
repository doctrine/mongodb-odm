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

    public function testEmbedOneDocumentWithMissingIdentifier()
    {
        $user = new EmbeddedIdTestUser();
        $user->embedOne = new DefaultIdStrategyNoneEmbeddedDocument();

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(get_class($user), $user->id);
    }

    public function testEmbedManyDocumentWithMissingIdentifier()
    {
        $user = new EmbeddedIdTestUser();
        $user->embedMany[] = new DefaultIdStrategyNoneEmbeddedDocument();

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(get_class($user), $user->id);
        foreach ($user->embedMany as $embed) {
            $this->assertNull($embed->id);
        }
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
