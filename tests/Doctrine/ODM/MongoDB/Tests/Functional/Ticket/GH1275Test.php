<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use function array_map;

class GH1275Test extends BaseTest
{
    public function testResortAtomicCollectionsFlipItems()
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
            ['Number One', 'Number Two', 'Number Three'],
            array_map($getNameCallback, $container->items->toArray())
        );

        $container->flip(1, 2);

        $this->dm->persist($container);
        $this->dm->flush();

        $this->dm->refresh($container);

        $this->assertSame(
            ['Number One','Number Three', 'Number Two'],
            array_map($getNameCallback, $container->items->toArray())
        );
    }

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
            ['Number One', 'Number Two', 'Number Three'],
            array_map($getNameCallback, $container->items->toArray())
        );

        $container->move($itemOne, -1);

        $this->dm->flush();
        $this->dm->refresh($container);

        $this->assertSame(
            ['Number One', 'Number Two', 'Number Three'],
            array_map($getNameCallback, $container->items->toArray())
        );

        $container->move($itemOne, 1);

        $this->dm->flush();
        $this->dm->refresh($container);

        $this->assertSame(
            ['Number Two', 'Number One', 'Number Three'],
            array_map($getNameCallback, $container->items->toArray())
        );

        $container->move($itemTwo, 2);

        $this->dm->flush();
        $this->dm->refresh($container);

        $this->assertSame(
            ['Number One', 'Number Three', 'Number Two'],
            array_map($getNameCallback, $container->items->toArray())
        );

        $container->move($itemTwo, 2);

        $this->dm->flush();
        $this->dm->refresh($container);

        $this->assertSame(
            ['Number One', 'Number Three', 'Number Two'],
            array_map($getNameCallback, $container->items->toArray())
        );

        $container->move($itemThree, -1);

        $this->dm->flush();
        $this->dm->refresh($container);

        $this->assertSame(
            ['Number Three', 'Number One', 'Number Two'],
            array_map($getNameCallback, $container->items->toArray())
        );

        $this->assertCount(3, $container->items);
    }

    public static function getCollectionStrategies()
    {
        return [
            'testResortWithStrategyAddToSet' => [ClassMetadata::STORAGE_STRATEGY_ADD_TO_SET],
            'testResortWithStrategySet' => [ClassMetadata::STORAGE_STRATEGY_SET],
            'testResortWithStrategySetArray' => [ClassMetadata::STORAGE_STRATEGY_SET_ARRAY],
            'testResortWithStrategyPushAll' => [ClassMetadata::STORAGE_STRATEGY_PUSH_ALL],
            'testResortWithStrategyAtomicSet' => [ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET],
            'testResortWithStrategyAtomicSetArray' => [ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET_ARRAY],
        ];
    }

    /**
     * @dataProvider getCollectionStrategies
     */
    public function testResortEmbedManyCollection($strategy)
    {
        $getNameCallback = function (Element $element) {
            return $element->name;
        };

        $container = new Container();
        $container->$strategy->add(new Element('one'));
        $container->$strategy->add(new Element('two'));
        $container->$strategy->add(new Element('three'));

        $this->dm->persist($container);
        $this->dm->flush();
        $this->dm->refresh($container);

        $this->assertSame(
            ['one', 'two', 'three'],
            array_map($getNameCallback, $container->$strategy->toArray())
        );

        $two = $container->$strategy->get(1);
        $three = $container->$strategy->get(2);
        $container->$strategy->set(1, $three);
        $container->$strategy->set(2, $two);

        $this->dm->flush();

        $this->dm->refresh($container);

        $this->assertSame(
            ['one', 'three', 'two'],
            array_map($getNameCallback, $container->$strategy->toArray())
        );
    }
}

/**
 * @ODM\Document(collection="item")
 */
class Item
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @var Container */
    protected $container;

    public function __construct(Container $c, $name)
    {
        $this->container = $c;
        $this->name = $name;
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class Element
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/**
 * @ODM\Document(collection="container")
 */
class Container
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceMany(
     *     targetDocument=Item::class,
     *     cascade={"refresh","persist"},
     *     orphanRemoval="true",
     *     strategy="atomicSet"
     * )
     */
    public $items;

    /**
     * @ODM\ReferenceOne(
     *     targetDocument=Item::class,
     *     cascade={"refresh"}
     * )
     */
    public $firstItem;

    /**
     * @ODM\EmbedMany(
     *     targetDocument=Element::class,
     *     strategy="addToSet"
     * )
     */
    public $addToSet;

    /**
     * @ODM\EmbedMany(
     *     targetDocument=Element::class,
     *     strategy="set"
     * )
     */
    public $set;

    /**
     * @ODM\EmbedMany(
     *     targetDocument=Element::class,
     *     strategy="setArray"
     * )
     */
    public $setArray;

    /**
     * @ODM\EmbedMany(
     *     targetDocument=Element::class,
     *     strategy="pushAll"
     * )
     */
    public $pushAll;

    /**
     * @ODM\EmbedMany(
     *     targetDocument=Element::class,
     *     strategy="atomicSet"
     * )
     */
    public $atomicSet;

    /**
     * @ODM\EmbedMany(
     *     targetDocument=Element::class,
     *     strategy="atomicSetArray"
     * )
     */
    public $atomicSetArray;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->addToSet = new ArrayCollection();
        $this->set = new ArrayCollection();
        $this->setArray = new ArrayCollection();
        $this->pushAll = new ArrayCollection();
        $this->atomicSet = new ArrayCollection();
        $this->atomicSetArray = new ArrayCollection();
    }

    public function add(Item $item)
    {
        $this->items->add($item);
        if ($this->items->count() !== 1) {
            return;
        }

        $this->firstItem = $item;
    }

    public function flip($a, $b)
    {
        $itemA = $this->items->get($a);
        $itemB = $this->items->get($b);

        $this->items->set($b, $itemA);
        $this->items->set($a, $itemB);
    }

    public function move(Item $item, $move)
    {
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
