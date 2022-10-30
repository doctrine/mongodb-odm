<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MappedSuperclassTest extends BaseTest
{
    public function testCRUD(): void
    {
        $e = new DocumentSubClass();
        $e->setId(1);
        $e->setName('Roman');
        $e->setMapped1(42);
        $e->setMapped2('bar');

        $related = new MappedSuperclassRelated1();
        $related->setId(1);
        $related->setName('Related');
        $e->setMappedRelated1($related);

        $this->dm->persist($related);
        $this->dm->persist($e);
        $this->dm->flush();
        $this->dm->clear();

        $e2 = $this->dm->find(DocumentSubClass::class, 1);
        self::assertNotNull($e2);
        self::assertEquals(1, $e2->getId());
        self::assertEquals('Roman', $e2->getName());
        self::assertNotNull($e2->getMappedRelated1());
        self::assertInstanceOf(MappedSuperclassRelated1::class, $e2->getMappedRelated1());
        self::assertEquals(42, $e2->getMapped1());
        self::assertEquals('bar', $e2->getMapped2());
    }
}

/** @ODM\MappedSuperclass */
class MappedSuperclassBase
{
    /**
     * @ODM\Field(type="string")
     *
     * @var int|string|null
     */
    private $mapped1;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $mapped2;

    /**
     * @ODM\ReferenceOne(targetDocument=MappedSuperclassRelated1::class)
     *
     * @var MappedSuperclassRelated1|null
     */
    private $mappedRelated1;

    /** @param int|string $val */
    public function setMapped1($val): void
    {
        $this->mapped1 = $val;
    }

    /** @return int|string|null */
    public function getMapped1()
    {
        return $this->mapped1;
    }

    public function setMapped2(string $val): void
    {
        $this->mapped2 = $val;
    }

    public function getMapped2(): ?string
    {
        return $this->mapped2;
    }

    public function setMappedRelated1(MappedSuperclassRelated1 $mappedRelated1): void
    {
        $this->mappedRelated1 = $mappedRelated1;
    }

    public function getMappedRelated1(): ?MappedSuperclassRelated1
    {
        return $this->mappedRelated1;
    }
}

/** @ODM\Document */
class MappedSuperclassRelated1
{
    /**
     * @ODM\Id(strategy="none")
     *
     * @var int|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}

/** @ODM\Document */
class DocumentSubClass extends MappedSuperclassBase
{
    /**
     * @ODM\Id(strategy="none")
     *
     * @var int|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
