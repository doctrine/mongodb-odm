<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

use function spl_object_hash;

class GH2730Test extends BaseTestCase
{
    public function testUniqueObjectIdentifier(): void
    {
        $document = new GH2730Document();
        $oid      = spl_object_hash($document);
        $this->dm->persist($document);
        $this->dm->flush();

        // Remove the document
        $this->dm->remove($document);
        $this->dm->flush();

        // Remove the last reference to the document
        unset($document);

        // Create a new document
        $document = new GH2730Document();
        $this->dm->persist($document);
        $this->dm->flush();

        self::assertSame(1, $this->countDocuments());

        // If this assertion fails in a future version of PHP, this test case can be skipped.
        self::assertSame($oid, spl_object_hash($document), 'PHP created a new object wit the same object hash');
    }

    public function testRemoveFlushPersist(): void
    {
        $document = new GH2730Document();
        $this->dm->persist($document);
        $this->dm->flush();

        // Remove the document
        $this->dm->remove($document);
        $this->dm->flush();

        // Re-persist the same document
        $this->dm->persist($document);
        $this->dm->flush();

        self::assertSame(1, $this->countDocuments());
    }

    public function testRemovePersist(): void
    {
        $document = new GH2730Document();
        $this->dm->persist($document);
        $this->dm->flush();

        // Remove the document
        $this->dm->remove($document);

        // Re-persist the same document
        $this->dm->persist($document);
        $this->dm->flush();

        self::assertSame(1, $this->countDocuments());
    }

    private function countDocuments(): int
    {
        return $this->dm->getDocumentCollection(GH2730Document::class)->countDocuments();
    }
}

#[ODM\Document]
class GH2730Document
{
    #[ODM\Id]
    public ?string $id = null;
}
