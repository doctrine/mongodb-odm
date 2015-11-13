<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH41275Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     *
     */
    public function testResortAtomicCollectionsSwitch()
    {
        $getNameCallback = function (Item $item) {
            return $item->name;
        };

        $container = new Container();
        $this->dm->persist($container);
        $this->dm->flush();

        $itemOne = new Item($container, 'Number One');
        $itemTwo = new Item($container, 'Number Two');
        $itemThree = new Item($container, 'Number Three');

        $this->dm->persist($itemOne);
        $this->dm->persist($itemTwo);
        $this->dm->persist($itemThree);
        $this->dm->flush();

        $container->add($itemOne);
        $container->add($itemTwo);
        $container->add($itemThree);

        $this->assertSame(
            array('Number One', 'Number Two', 'Number Three'),
            array_map($getNameCallback, $container->items->toArray())
        );

        $container->flip(1,2);

        $this->dm->persist($container);
        $this->dm->flush();

        $this->dm->refresh($container);

        $this->assertSame(
            array('Number One','Number Three', 'Number Two'),
            array_map($getNameCallback, $container->items->toArray())
        );
    }
    /**
     *
     */
    public function testResortAtomicCollections()
    {
        $getNameCallback = function (Item $item) {
            return $item->name;
        };

        $container = new Container();
        $this->dm->persist($container);
        $this->dm->flush();

        $itemOne = new Item($container, 'Number One');
        $itemTwo = new Item($container, 'Number Two');
        $itemThree = new Item($container, 'Number Three');

        $this->dm->persist($itemOne);
        $this->dm->persist($itemTwo);
        $this->dm->persist($itemThree);
        $this->dm->flush();

        $container->add($itemOne);
        $container->add($itemTwo);
        $container->add($itemThree);

        $this->assertSame(
            array('Number One', 'Number Two', 'Number Three'),
            array_map($getNameCallback, $container->items->toArray())
        );

        $this->dm->refresh($container);

        $container->move($itemOne, -1);

        $this->dm->persist($container);
        $this->dm->flush();
        $this->dm->refresh($container);

        $this->assertSame(
            array('Number One', 'Number Two', 'Number Three'),
            array_map($getNameCallback, $container->items->toArray())
        );

        $container->move($itemOne, 1);

        $this->dm->persist($container);
        $this->dm->flush();

        $this->assertSame(
            array('Number Two', 'Number One', 'Number Three'),
            array_map($getNameCallback, $container->items->toArray())
        );

        $container->move($itemTwo, 2);

        $this->dm->persist($container);
        $this->dm->flush();

        $this->assertSame(
            array('Number One', 'Number Three', 'Number Two'),
            array_map($getNameCallback, $container->items->toArray())
        );

        $container->move($itemTwo, 2);

        $this->dm->persist($container);
        $this->dm->flush();

        $this->assertSame(
            array('Number One', 'Number Three', 'Number Two'),
            array_map($getNameCallback, $container->items->toArray())
        );

        $container->move($itemThree, -1);

        $this->dm->persist($container);

        $this->assertSame(
            array('Number Three', 'Number One', 'Number Two'),
            array_map($getNameCallback, $container->items->toArray())
        );

        $this->assertCount(3, $container->items);
    }
}

/**
 * @ODM\Document(collection="item")
 * @ODM\DiscriminatorField("type")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 */
class Item {
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $c, $name)
    {
        $this->container = $c;
        $this->name = $name;
    }
}

/**
 * @ODM\Document(collection="container")
 */
class Container {
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceMany(
    *     targetDocument="Item",
    *     cascade={"refresh","persist"},
    *     orphanRemoval="true",
    *     strategy="atomicSet"
    * )
    */
    public $items;

    /**
     * @ODM\ReferenceOne(
     *     targetDocument="Item",
     *     cascade={"refresh"}
     * )
     */
    public $firstItem;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function add(Item $item)
    {
        $this->items->add($item);
        if ($this->items->count() == 1) {
            $this->firstItem = $item;
        }
    }

    public function flip($a, $b)
    {
        $itemA = $this->items->get($a);
        $itemB = $this->items->get($b);

        $this->items->set($b, $itemA);
        $this->items->set($a, $itemB);
    }

    public function move(Item $item, $move) {
        if ($move === 0) {
            return $this;
        }

        $currentPosition = $this->items->indexOf($item);
        if ($currentPosition === false) {
            throw new \InvalidArgumentException('Cannot move an item which was not previously added');
        }

        $newPosition = $currentPosition + $move;
        if ($newPosition < 0) {
            $newPosition = 0;
        } elseif ($newPosition >= $this->items->count()) {
            $newPosition = $this->items->count() - 1;
        }

        if ($move < 0) {
            for ($index = $currentPosition; $index > $newPosition; $index--) {
                $this->items->set($index, $this->items->get($index - 1));
            }
        } else {
            for ($index = $currentPosition; $index < $newPosition; $index++) {
                $this->items->set($index, $this->items->get($index + 1));
            }
        }

        $this->items->set($newPosition, $item);
    }
}
