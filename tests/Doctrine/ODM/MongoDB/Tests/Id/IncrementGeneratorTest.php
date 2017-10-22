<?php

namespace Doctrine\ODM\MongoDB\Tests\Id;

use Doctrine\ODM\MongoDB\Id\IncrementGenerator;
use Documents\User;

class IncrementGeneratorTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testIdGeneratorWithStartingValue()
    {
        $generator = new IncrementGenerator();
        $generator->setStartingId(10);

        $collection = $this->dm->getConnection()->selectCollection(DOCTRINE_MONGODB_DATABASE, 'doctrine_increment_ids');

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

        $collection = $this->dm->getConnection()->selectCollection(DOCTRINE_MONGODB_DATABASE, 'doctrine_increment_ids');
        $result = $collection->findOne(['_id' => 'users']);
        self::assertSame(1, $result['current_id']);
    }
}
