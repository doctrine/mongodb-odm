<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class MODM62Test extends BaseTestCase
{
    public function testTest(): void
    {
        $test = new MODM62Document();
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->refresh($test);

        $test->setB(['test', 'test2']);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(MODM62Document::class, $test->id);
        self::assertEquals(['test', 'test2'], $test->b);
    }
}

#[ODM\Document(collection: 'modm62_users')]
class MODM62Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string[] */
    #[ODM\Field(type: 'collection')]
    public $b = ['ok'];

    /** @param string[] $b */
    public function setB(array $b): void
    {
        $this->b = $b;
    }
}
