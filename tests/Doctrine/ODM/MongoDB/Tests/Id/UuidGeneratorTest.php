<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Id;

use Doctrine\ODM\MongoDB\Id\UuidGenerator;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;

use const DOCTRINE_MONGODB_DATABASE;

class UuidGeneratorTest extends BaseTest
{
    public function testUuidGeneratorCreates16ByteUuid(): void
    {
        $generator = new UuidGenerator();
        $generator->setSalt(date('now'));

        $this->assertSame(16, strlen($generator->generate($this->dm, new User())));
    }
}
