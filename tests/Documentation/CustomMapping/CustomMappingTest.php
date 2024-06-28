<?php

declare(strict_types=1);

namespace Documentation\CustomMapping;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;

class CustomMappingTest extends BaseTestCase
{
    public function testTest(): void
    {
        Type::addType('date_with_timezone', DateTimeWithTimezoneType::class);
        Type::overrideType('date_immutable', DateTimeWithTimezoneType::class);

        $thing       = new Thing();
        $thing->date = new DateTimeImmutable('2021-01-01 00:00:00', new DateTimeZone('Africa/Tripoli'));

        $this->dm->persist($thing);
        $this->dm->flush();
        $this->dm->clear();

        $result = $this->dm->find(Thing::class, $thing->id);
        $this->assertEquals($thing->date, $result->date);
        $this->assertEquals('Africa/Tripoli', $result->date->getTimezone()->getName());

        // Ensure we don't need to handle null values
        $nothing = new Thing();

        $this->dm->persist($nothing);
        $this->dm->flush();
        $this->dm->clear();

        $result = $this->dm->find(Thing::class, $nothing->id);
        $this->assertNull($result->date);
    }
}
