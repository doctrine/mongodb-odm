<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Id\UuidGenerator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH1525Test extends BaseTestCase
{
    public function testEmbedCloneTwoFlushesPerDocument(): void
    {
        $embedded = new GH1525Embedded('embedded');

        $embedMany = new GH1525Embedded('embedMany');

        for ($i = 0; $i < 2; ++$i) {
            $parent = new GH1525Document('test' . $i);
            $this->dm->persist($parent);
            $this->dm->flush();

            $parent->embedded = $embedded;
            $parent->embedMany->add($embedMany);
            $this->dm->flush();
        }

        $this->dm->clear();

        for ($i = 0; $i < 2; ++$i) {
            $test = $this->dm->getRepository(GH1525Document::class)->findOneBy(['name' => 'test' . $i]);

            self::assertInstanceOf(GH1525Document::class, $test);

            self::assertInstanceOf(GH1525Embedded::class, $test->embedded);
            self::assertSame($test->embedded->name, $embedded->name);

            self::assertCount(1, $test->embedMany);
            self::assertInstanceOf(GH1525Embedded::class, $test->embedMany[0]);
            self::assertSame($test->embedMany[0]->name, $embedMany->name);
        }
    }

    public function testEmbedCloneWithIdStrategyNoneOnParentAndEarlyPersist(): void
    {
        $uuidGen  = new UuidGenerator();
        $embedded = new GH1525Embedded('embedded');

        $count = 2;
        for ($i = 0; $i < $count; ++$i) {
            $parent = new GH1525DocumentIdStrategyNone($uuidGen->generateV4(), 'test' . $i);
            $this->dm->persist($parent);
            $parent->embedded = $embedded;
            $this->dm->flush();
        }

        $this->dm->clear();

        for ($i = 0; $i < $count; ++$i) {
            $test = $this->dm->getRepository(GH1525DocumentIdStrategyNone::class)->findOneBy(['name' => 'test' . $i]);

            self::assertInstanceOf(GH1525DocumentIdStrategyNone::class, $test);

            self::assertInstanceOf(GH1525Embedded::class, $test->embedded);
            self::assertSame($test->embedded->name, $embedded->name);
        }
    }

    public function testEmbedCloneWithIdStrategyNoneOnParentAndLatePersist(): void
    {
        $uuidGen  = new UuidGenerator();
        $embedded = new GH1525Embedded('embedded');

        $count = 2;
        for ($i = 0; $i < $count; ++$i) {
            $parent           = new GH1525DocumentIdStrategyNone($uuidGen->generateV4(), 'test' . $i);
            $parent->embedded = $embedded;
            $this->dm->persist($parent);
            $this->dm->flush();
        }

        $this->dm->clear();

        for ($i = 0; $i < $count; ++$i) {
            $test = $this->dm->getRepository(GH1525DocumentIdStrategyNone::class)->findOneBy(['name' => 'test' . $i]);

            self::assertInstanceOf(GH1525DocumentIdStrategyNone::class, $test);

            self::assertInstanceOf(GH1525Embedded::class, $test->embedded);
            self::assertSame($test->embedded->name, $embedded->name);
        }
    }
}

#[ODM\Document(collection: 'document_test')]
class GH1525Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var GH1525Embedded|null */
    #[ODM\EmbedOne(targetDocument: GH1525Embedded::class)]
    public $embedded;

    /** @var Collection<int, GH1525Embedded> */
    #[ODM\EmbedMany(targetDocument: GH1525Embedded::class)]
    public $embedMany;

    public function __construct(string $name)
    {
        $this->name      = $name;
        $this->embedMany = new ArrayCollection();
    }
}

#[ODM\Document(collection: 'document_test_with_auto_ids')]
class GH1525DocumentIdStrategyNone
{
    /** @var string */
    #[ODM\Id(strategy: 'NONE')]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var GH1525Embedded|null */
    #[ODM\EmbedOne(targetDocument: GH1525Embedded::class)]
    public $embedded;

    public function __construct(string $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}

#[ODM\EmbeddedDocument]
class GH1525Embedded
{
    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
