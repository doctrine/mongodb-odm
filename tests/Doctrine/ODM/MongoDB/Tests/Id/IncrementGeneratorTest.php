<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Id;

use Doctrine\ODM\MongoDB\Id\IncrementGenerator;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;
use const DOCTRINE_MONGODB_DATABASE;

class IncrementGeneratorTest extends BaseTest
{
    public function testIdGeneratorWithStartingValue()
    {
        $generator = new IncrementGenerator();
        $generator->setStartingId(10);

        $collection = $this->dm->getClient()->selectCollection(DOCTRINE_MONGODB_DATABASE, 'doctrine_increment_ids');

        $this->assertSame(10, $generator->generate($this->dm, new User()));
        $result = $collection->findOne(['_id' => 'users']);
        self::assertSame(10, $result['current_id']);

        $this->assertSame(11, $generator->generate($this->dm, new User()));
        $result = $collection->findOne(['_id' => 'users']);
        self::assertSame(11, $result['current_id']);
    }

    public function testUsesOneAsStartingValueIfNotOverridden()
    {
        $generator = new IncrementGenerator();

        $this->assertSame(1, $generator->generate($this->dm, new User()));

        $collection = $this->dm->getClient()->selectCollection(DOCTRINE_MONGODB_DATABASE, 'doctrine_increment_ids');
        $result = $collection->findOne(['_id' => 'users']);
        self::assertSame(1, $result['current_id']);
    }
}
