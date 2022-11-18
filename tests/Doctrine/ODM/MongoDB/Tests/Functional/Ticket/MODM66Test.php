<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM66Test extends BaseTest
{
    public function testTest(): void
    {
        $b1 = new MODM52B('first');
        $a  = new MODM52A([$b1]);
        $this->dm->persist($a);
        $this->dm->flush();
        $b2 = new MODM52B('second');
        $a->getB()->add($b2);
        $this->dm->flush();

        $this->dm->refresh($a);
        $b = $a->getB()->toArray();

        self::assertCount(2, $b);

        self::assertEquals([
            $b1->getId(),
            $b2->getId(),
        ], [
            $b[0]->getId(),
            $b[1]->getId(),
        ]);
    }

    public function testRefresh(): void
    {
        $b1 = new MODM52B('first');
        $a  = new MODM52A([$b1]);
        $this->dm->persist($a);
        $this->dm->flush();
        $b2 = new MODM52B('second');

        $this->dm->refresh($a);

        $a->getB()->add($b2);
        $this->dm->flush();
        $this->dm->refresh($a);
        $b = $a->getB()->toArray();

        self::assertCount(2, $b);

        self::assertEquals([
            $b1->getId(),
            $b2->getId(),
        ], [
            $b[0]->getId(),
            $b[1]->getId(),
        ]);
    }
}

/** @ODM\Document */
class MODM52A
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\ReferenceMany(targetDocument=MODM52B::class, cascade="all")
     *
     * @var Collection<int, MODM52B>
     */
    protected $b;

    /** @param array<MODM52B> $b */
    public function __construct(array $b)
    {
        $this->b = new ArrayCollection($b);
    }

    /** @return Collection<int, MODM52B> */
    public function getB(): Collection
    {
        return $this->b;
    }
}

/** @ODM\Document */
class MODM52B
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $value;

    public function __construct(string $v)
    {
        $this->value = $v;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
