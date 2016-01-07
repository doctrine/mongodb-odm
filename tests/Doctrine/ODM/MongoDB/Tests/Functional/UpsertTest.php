<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoId;

class UpsertTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * Tests for "MongoCursorException: Cannot apply $push/$pushAll modifier to non-array" error.
     *
     * Embedded document with provided id should not be upserted.
     */
    public function testUpsertEmbedManyDoesNotCreateObject()
    {
        $test = new UpsertTestUser();
        $test->id = (string) new MongoId();

        $embedded = new UpsertTestUserEmbedded();
        $embedded->id = (string) new MongoId();
        $embedded->test = 'test';

        $test->embedMany[] = $embedded;

        $this->dm->persist($test);

        $this->assertFalse($this->uow->isScheduledForInsert($test));
        $this->assertTrue($this->uow->isScheduledForUpsert($test));

        $this->assertTrue($this->uow->isScheduledForInsert($embedded));
        $this->assertFalse($this->uow->isScheduledForUpsert($embedded));

        $this->dm->flush();
    }
}

/** @ODM\Document */
class UpsertTestUser
{
    /**
     * @ODM\Id
     */
    public $id;

    /**
     * @ODM\EmbedMany(targetDocument="UpsertTestUserEmbedded")
     */
    public $embedMany;
}

/** @ODM\EmbeddedDocument */
class UpsertTestUserEmbedded
{
    /**
     * @ODM\Id
     */
    public $id;

    /** @ODM\Field(type="string") */
    public $test;
}
