<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

use function spl_object_id;

class GH3088Test extends BaseTestCase
{
    public function testRemoveBeforeFlushThenPersistNewDocument(): void
    {
        $document = new GH3088();
        $oid      = spl_object_id($document);
        $this->dm->persist($document);

        // Remove the document before it was written
        $this->dm->remove($document);
        self::assertCount(0, $this->dm->getUnitOfWork()->getScheduledDocumentInsertions());

        // Remove the last reference to the document
        unset($document);

        // Create a new document
        $document = new GH3088();

        // If this assertion fails in a future version of PHP, this test case can be skipped.
        self::assertSame($oid, spl_object_id($document), 'PHP created a new object with the same object id');
        self::assertNull($this->dm->getUnitOfWork()->getDocumentIdentifier($document), 'The identifier of the removed document is forgotten');

        $this->dm->persist($document);
        self::assertCount(1, $this->dm->getUnitOfWork()->getScheduledDocumentInsertions());

        $this->dm->flush();

        self::assertSame(1, $this->countDocuments());
    }

    public function testNewDocumentReusingTheObjectIdIsNotReportedAsManaged(): void
    {
        $document = new GH3088();
        $oid      = spl_object_id($document);
        $this->dm->persist($document);
        $id = $document->id;

        $this->dm->remove($document);
        unset($document);

        // Create a new document, reusing the object id of the removed one
        $document = new GH3088();

        // If this assertion fails in a future version of PHP, this test case can be skipped.
        self::assertSame($oid, spl_object_id($document), 'PHP created a new object with the same object id');

        // Another document takes the identifier generated for the removed one
        $other     = new GH3088();
        $other->id = $id;
        $this->dm->persist($other);
        self::assertTrue($this->dm->getUnitOfWork()->isInIdentityMap($other));

        self::assertFalse($this->dm->getUnitOfWork()->isInIdentityMap($document), 'A new document must not be reported as managed');
    }

    private function countDocuments(): int
    {
        return $this->dm->getDocumentCollection(GH3088::class)->countDocuments();
    }
}

#[ODM\Document]
class GH3088
{
    #[ODM\Id]
    public ?string $id = null;
}
