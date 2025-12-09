<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class MODM48Test extends BaseTestCase
{
    public function testTest(): void
    {
        $a    = new MODM48A();
        $a->b = new MODM48B();
        $this->dm->persist($a);
        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->find(MODM48A::class, $a->id);
        self::assertNotNull($a);

        $a->getB()->setVal('test');

        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->find(MODM48A::class, $a->id);
        self::assertEquals('test', $a->getB()->getVal());
    }
}

#[ODM\Document]
class MODM48A
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var MODM48B|null */
    #[ODM\EmbedOne(targetDocument: MODM48B::class)]
    public $b;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getB(): ?MODM48B
    {
        return $this->b;
    }

    public function setB(MODM48B $b): void
    {
        $this->b = $b;
    }
}

#[ODM\EmbeddedDocument]
class MODM48B
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $val;

    public function setVal(string $val): void
    {
        $this->val = $val;
    }

    public function getVal(): ?string
    {
        return $this->val;
    }
}
