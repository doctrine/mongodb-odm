<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM48Test extends BaseTest
{
    public function testTest(): void
    {
        $a    = new MODM48A();
        $a->b = new MODM48B();
        $this->dm->persist($a);
        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->find(MODM48A::class, $a->id);
        $this->assertNotNull($a);

        $a->getB()->setVal('test');

        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->find(MODM48A::class, $a->id);
        $this->assertEquals('test', $a->getB()->getVal());
    }
}

/** @ODM\Document */
class MODM48A
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedOne(targetDocument=MODM48B::class)
     *
     * @var MODM48B|null
     */
    public $b;

    public function getId()
    {
        return $this->id;
    }

    public function getB()
    {
        return $this->b;
    }

    public function setB($b): void
    {
        $this->b = $b;
    }
}

/** @ODM\EmbeddedDocument */
class MODM48B
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $val;

    public function setVal($val): void
    {
        $this->val = $val;
    }

    public function getVal()
    {
        return $this->val;
    }
}
