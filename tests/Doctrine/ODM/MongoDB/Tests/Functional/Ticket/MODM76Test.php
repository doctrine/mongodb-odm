<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class MODM76Test extends BaseTestCase
{
    public function testTest(): void
    {
        $c1 = new MODM76C();
        $c2 = new MODM76C();

        $b = new MODM76B($c1);
        $a = new MODM76A([$b], [$c1]);

        $this->dm->persist($a);
        $this->dm->persist($b);
        $this->dm->persist($c1);
        $this->dm->persist($c2);
        $this->dm->flush();

        self::assertNotNull($a->getId());
    }
}

#[ODM\Document]
class MODM76A
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    protected $test = 'test';

    /** @var Collection<int, MODM76B> */
    #[ODM\EmbedMany(targetDocument: MODM76B::class)]
    protected $b;

    /** @var Collection<int, MODM76C> */
    #[ODM\ReferenceMany(targetDocument: MODM76C::class)]
    protected $c;

    /**
     * @param MODM76B[] $b
     * @param MODM76C[] $c
     */
    public function __construct(array $b, array $c)
    {
        $this->b = new ArrayCollection($b);
        $this->c = new ArrayCollection($c);
    }

    /** @return Collection<int, MODM76B> */
    public function getB(): Collection
    {
        return $this->b;
    }

    /** @return Collection<int, MODM76C> */
    public function getC(): Collection
    {
        return $this->c;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}

#[ODM\EmbeddedDocument]
class MODM76B
{
    /** @var MODM76C */
    #[ODM\ReferenceOne(targetDocument: MODM76C::class)]
    protected $c;

    public function __construct(MODM76C $c)
    {
        $this->c = $c;
    }

    public function getC(): MODM76C
    {
        return $this->c;
    }
}

#[ODM\Document]
class MODM76C
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;
}
