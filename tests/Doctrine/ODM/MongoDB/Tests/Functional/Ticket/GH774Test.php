<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

use function get_class;

class GH774Test extends BaseTest
{
    public function testUpsert(): void
    {
        $id = (string) new ObjectId();

        $thread            = new GH774Thread();
        $thread->id        = $id;
        $thread->permalink = 'test';

        $this->dm->persist($thread);
        $this->dm->flush();
        $this->dm->clear();

        $thread = $this->dm->find(get_class($thread), $id);
        $this->assertNotNull($thread);
        $this->assertEquals('test', $thread->permalink);
    }

    protected function createMetadataDriverImpl()
    {
        return new XmlDriver(__DIR__ . '/GH774');
    }
}

abstract class GH774AbstractThread
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /** @var string|null */
    public $permalink;
}

class GH774Thread extends GH774AbstractThread
{
}
