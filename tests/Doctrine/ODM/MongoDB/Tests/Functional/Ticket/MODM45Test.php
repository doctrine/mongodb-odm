<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM45Test extends BaseTest
{
    public function testTest(): void
    {
        $a = new MODM45A();
        $a->setB(new MODM45B());

        $this->dm->persist($a);
        $this->dm->flush();
        $this->dm->clear();

        $a = $this->dm->find(MODM45A::class, $a->getId());
        $c = ($a->getB() !== null);
        $this->assertTrue($c); // returns false, while expecting true
    }
}

/** @ODM\Document(collection="modm45_test") */
class MODM45A
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\EmbedOne(targetDocument=MODM45B::class) */
    protected $b;

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
class MODM45B
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $val;

    public function setVal($val): void
    {
        $this->val = $val;
    }

    public function getVal()
    {
        return $this->val;
    }
}
