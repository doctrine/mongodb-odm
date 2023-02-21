<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function assert;

class MODM29Test extends BaseTest
{
    public function testTest(): void
    {
        $collection = new ArrayCollection([
            new MODM29Embedded('0'),
            new MODM29Embedded('1'),
            new MODM29Embedded('2'),
        ]);

        // TEST CASE:
        $doc = new MODM29Doc($collection);

        $this->dm->persist($doc);
        $this->dm->flush();

        assert(isset($collection[0], $collection[1], $collection[2]));
        // place element '0' after '1'
        /** @var ArrayCollection<int, MODM29Embedded> $collection */
        $collection = new ArrayCollection([
            $collection[1],
            $collection[0],
            $collection[2],
        ]);

        $doc->set($collection);

        // changing value together with reordering causes issue when saving:
        $collection[1]->set('tmp');

        $this->dm->persist($doc);
        $this->dm->flush();

        $this->dm->refresh($doc);

        $array = [];
        foreach ($doc->get() as $value) {
            $array[] = $value->get();
        }

        self::assertEquals(['1', 'tmp', '2'], $array);
    }
}

/** @ODM\Document */
class MODM29Doc
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\EmbedMany(targetDocument=MODM29Embedded::class, strategy="set")
     *
     * @var Collection<int, MODM29Embedded>
     */
    protected $collection;

    /** @param Collection<int, MODM29Embedded> $c */
    public function __construct(Collection $c)
    {
        $this->set($c);
    }

    /** @param Collection<int, MODM29Embedded> $c */
    public function set(Collection $c): void
    {
        $this->collection = $c;
    }

    /** @return Collection<int, MODM29Embedded> */
    public function get(): Collection
    {
        return $this->collection;
    }
}

/** @ODM\EmbeddedDocument */
class MODM29Embedded
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $val;

    public function __construct(string $val)
    {
        $this->set($val);
    }

    public function get(): string
    {
        return $this->val;
    }

    public function set(string $val): void
    {
        $this->val = $val;
    }
}
