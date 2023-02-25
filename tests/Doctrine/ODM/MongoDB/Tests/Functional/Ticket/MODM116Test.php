<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

use function array_values;
use function get_class;

class MODM116Test extends BaseTestCase
{
    public function testIssue(): void
    {
        $parent = new MODM116Parent();
        $parent->setName('test');
        $parent->setChild(new MODM116Child());
        $this->dm->persist($parent->getChild());
        $this->dm->persist($parent);
        $this->dm->flush();
        $this->dm->clear();

        $parent = $this->dm->find(get_class($parent), $parent->getId());

        $parent->getChild()->setName('ok');
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(get_class($parent))->find()->toArray();
        $check = array_values($check);
        self::assertCount(1, $check);
        self::assertEquals('test', $check[0]['name']);

        $check = $this->dm->getDocumentCollection(get_class($parent->getChild()))->find()->toArray();
        $check = array_values($check);
        self::assertCount(1, $check);
        self::assertEquals('ok', $check[0]['name']);
    }
}

/** @ODM\Document @ODM\InheritanceType("COLLECTION_PER_CLASS") **/
class MODM116Parent
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    /**
     * @ODM\ReferenceOne(targetDocument=MODM116Child::class) *
     *
     * @var MODM116Child|null
     */
    private $child;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getChild(): ?MODM116Child
    {
        return $this->child;
    }

    public function setChild(MODM116Child $child): void
    {
        $this->child = $child;
    }
}

/** @ODM\Document **/
class MODM116Child extends MODM116Parent
{
}
