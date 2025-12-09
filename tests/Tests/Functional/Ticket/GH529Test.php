<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;

class GH529Test extends BaseTestCase
{
    public function testAutoIdWithConsistentValues(): void
    {
        $identifier = new ObjectId();
        $doc        = new GH529AutoIdDocument();
        $doc->id    = $identifier;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find($doc::class, $doc->id);

        self::assertNotNull($doc);
        self::assertEquals($identifier, $doc->id);
    }

    public function testCustomIdType(): void
    {
        /* All values are consistent for CustomIdType, since the PHP and DB
         * conversions return the value as-is.
         */
        $doc     = new GH529CustomIdDocument();
        $doc->id = 'foo';

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find($doc::class, $doc->id);

        self::assertNotNull($doc);
        self::assertSame('foo', $doc->id);
    }

    public function testIntIdWithConsistentValues(): void
    {
        $doc     = new GH529IntIdDocument();
        $doc->id = 1;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find($doc::class, $doc->id);

        self::assertNotNull($doc);
        self::assertSame(1, $doc->id);
    }

    public function testIntIdWithInconsistentValues(): void
    {
        $doc     = new GH529IntIdDocument();
        $doc->id = 3.14;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find($doc::class, $doc->id);

        self::assertNotNull($doc);
        self::assertNotEquals(3.14, $doc->id);
    }
}

#[ODM\Document]
class GH529AutoIdDocument
{
    /** @var ObjectId|null */
    #[ODM\Id]
    public $id;
}

#[ODM\Document]
class GH529CustomIdDocument
{
    /** @var string|null */
    #[ODM\Id(strategy: 'none', type: 'custom_id')]
    public $id;
}

#[ODM\Document]
class GH529IntIdDocument
{
    /** @var float|int|null */
    #[ODM\Id(strategy: 'none', type: 'int')]
    public $id;
}
