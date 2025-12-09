<?php

declare(strict_types=1);

namespace Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\EncryptQuery;
use PHPUnit\Framework\TestCase;

class EncryptQueryTest extends TestCase
{
    public function testAlias(): void
    {
        // @phpstan-ignore class.notFound
        self::assertSame(EncryptQuery::Equality, \Doctrine\ODM\MongoDB\Mapping\Annotations\EncryptQuery::Equality);
        // @phpstan-ignore class.notFound
        self::assertSame(EncryptQuery::Range, \Doctrine\ODM\MongoDB\Mapping\Annotations\EncryptQuery::Range);
    }
}
