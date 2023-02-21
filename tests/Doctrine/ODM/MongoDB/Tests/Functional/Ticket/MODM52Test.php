<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function count;

class MODM52Test extends BaseTest
{
    public function testTest(): void
    {
        $emb = new MODM52Embedded([new MODM52Embedded(null, 'c1'), new MODM52Embedded(null, 'c2')], 'b');
        $doc = new MODM52Doc([$emb], 'a');

        $this->dm->persist($doc);
        $this->dm->flush();

        $this->dm->refresh($doc);

        // change nested embedded collection:
        $doc->getItem(0)->removeItem(1);
        $before = count($doc->getItem(0)->getItems());

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->refresh($doc);

        $after = count($doc->getItem(0)->getItems());
        self::assertEquals(1, $before);
        self::assertEquals(1, $after);
    }
}

/** @ODM\MappedSuperClass */
class MODM52Container
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $value;

    /**
     * @ODM\EmbedMany(targetDocument=MODM52Embedded::class, strategy="set")
     *
     * @var Collection<int, MODM52Embedded>|array<MODM52Embedded>
     */
    public $items = [];

    /** @param array<MODM52Embedded>|null $items */
    public function __construct(?array $items = null, ?string $value = null)
    {
        if ($items) {
            $this->items = $items;
        }

        $this->value = $value;
    }

    /** @return Collection<int, MODM52Embedded>|array<MODM52Embedded> */
    public function getItems()
    {
        return $this->items;
    }

    public function getItem(int $index): MODM52Embedded
    {
        return $this->items[$index];
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i]);
    }
}

/** @ODM\EmbeddedDocument */
class MODM52Embedded extends MODM52Container
{
}

/** @ODM\Document */
class MODM52Doc extends MODM52Container
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;
}
