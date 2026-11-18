<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class IncrementUpsertTest extends BaseTestCase
{
    public function testDecimal128IncrementOnUpsertWithoutPersistedValue(): void
    {
        $document         = new IncrementUpsertDocument();
        $document->id     = 'increment-upsert';
        $document->amount = '5';

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(IncrementUpsertDocument::class, 'increment-upsert');

        self::assertInstanceOf(IncrementUpsertDocument::class, $document);
        self::assertSame('5', $document->amount);
    }
}

#[ODM\Document]
class IncrementUpsertDocument
{
    /** @var string|null */
    #[ODM\Id(strategy: 'none')]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'decimal128', strategy: 'increment')]
    public $amount;
}
