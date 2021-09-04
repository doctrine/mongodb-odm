<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

class MODM47Test extends BaseTest
{
    public function testTest(): void
    {
        $a = [
            '_id' => new ObjectId(),
            'c' => 'c value',
        ];
        $this->dm->getDocumentCollection(MODM47A::class)->insertOne($a);

        $a = $this->dm->find(MODM47A::class, $a['_id']);
        $this->assertEquals('c value', $a->b);
    }
}

/** @ODM\Document */
class MODM47A
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $b = 'tmp';

    /** @ODM\AlsoLoad("c") */
    public function renameC(string $c): void
    {
        $this->b = $c;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
