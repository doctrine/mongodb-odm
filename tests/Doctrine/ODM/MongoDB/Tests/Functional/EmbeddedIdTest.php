<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;

class EmbeddedIdTest extends BaseTestCase
{
    public function testEmbeddedIdsAreGenerated(): void
    {
        $test = new DefaultIdEmbeddedDocument();

        $this->dm->persist($test);

        self::assertNotNull($test->id);
    }

    public function testEmbeddedIdsAreNotOverwritten(): void
    {
        $id       = (string) new ObjectId();
        $test     = new DefaultIdEmbeddedDocument();
        $test->id = $id;

        $this->dm->persist($test);

        self::assertEquals($id, $test->id);
    }

    public function testEmbedOneDocumentWithMissingIdentifier(): void
    {
        $user           = new EmbeddedStrategyNoneIdTestUser();
        $user->embedOne = new DefaultIdStrategyNoneEmbeddedDocument();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Doctrine\ODM\MongoDB\Tests\Functional\DefaultIdStrategyNoneEmbeddedDocument uses NONE identifier ' .
            'generation strategy but no identifier was provided when persisting.',
        );
        $this->dm->persist($user);
    }

    public function testEmbedManyDocumentWithMissingIdentifier(): void
    {
        $user              = new EmbeddedStrategyNoneIdTestUser();
        $user->embedMany[] = new DefaultIdStrategyNoneEmbeddedDocument();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Doctrine\ODM\MongoDB\Tests\Functional\DefaultIdStrategyNoneEmbeddedDocument uses NONE identifier ' .
            'generation strategy but no identifier was provided when persisting.',
        );
        $this->dm->persist($user);
    }
}

#[ODM\Document]
class EmbeddedIdTestUser
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var DefaultIdEmbeddedDocument|null */
    #[ODM\EmbedOne(targetDocument: DefaultIdEmbeddedDocument::class)]
    public $embedOne;

    /** @var Collection<int, DefaultIdEmbeddedDocument>|array<DefaultIdEmbeddedDocument> */
    #[ODM\EmbedMany(targetDocument: DefaultIdEmbeddedDocument::class)]
    public $embedMany = [];
}

#[ODM\Document]
class EmbeddedStrategyNoneIdTestUser
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var DefaultIdStrategyNoneEmbeddedDocument|null */
    #[ODM\EmbedOne(targetDocument: DefaultIdStrategyNoneEmbeddedDocument::class)]
    public $embedOne;

    /** @var Collection<int, DefaultIdStrategyNoneEmbeddedDocument>|array<DefaultIdStrategyNoneEmbeddedDocument> */
    #[ODM\EmbedMany(targetDocument: DefaultIdStrategyNoneEmbeddedDocument::class)]
    public $embedMany = [];
}

#[ODM\EmbeddedDocument]
class DefaultIdEmbeddedDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}

#[ODM\EmbeddedDocument]
class DefaultIdStrategyNoneEmbeddedDocument
{
    /** @var string|null */
    #[ODM\Id(strategy: 'none')]
    public $id;
}
