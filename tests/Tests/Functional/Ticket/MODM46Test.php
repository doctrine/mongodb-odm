<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;

class MODM46Test extends BaseTestCase
{
    public function testTest(): void
    {
        $a = [
            '_id' => new ObjectId(),
            'c' => ['value' => 'value'],
        ];
        $this->dm->getDocumentCollection(MODM46A::class)->insertOne($a);

        $a = $this->dm->find(MODM46A::class, $a['_id']);

        self::assertTrue(isset($a->b));
        self::assertEquals('value', $a->b->value);
    }
}

#[ODM\Document]
class MODM46A
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var MODM46AB|null */
    #[ODM\EmbedOne(targetDocument: MODM46AB::class)]
    #[ODM\AlsoLoad('c')]
    public $b;
}

#[ODM\EmbeddedDocument]
class MODM46AB
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $value;
}
