<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\UnitOfWork;

use function in_array;
use function spl_object_hash;

class GH1017Test extends BaseTest
{
    public function testSPLObjectHashCollisionOnReplacingEmbeddedDoc(): void
    {
        $usedHashes = [];
        $owner      = new GH1017Document();
        $this->dm->persist($owner);
        $this->dm->flush();

        $maxTries = 10;

        // Keep replacing the embedded object
        // until the same object hash is returned
        for ($i = 0; $i < $maxTries; $i++) {
            unset($owner->embedded);

            $owner->embedded = new GH1017EmbeddedDocument();
            $oid             = spl_object_hash($owner->embedded);
            if (in_array($oid, $usedHashes)) {
                // Collision found, let's test state of embedded doc
                self::assertEquals(
                    UnitOfWork::STATE_NEW,
                    $this->uow->getDocumentState($owner->embedded),
                    'A newly created object should be treated as NEW by UOW',
                );

                return;
            }

            $usedHashes[] = $oid;
            $this->dm->flush();
        }

        // At the time of writing this test,
        // collision was always found when $i == 2

        $this->markTestSkipped('No object hash collision encountered after ' . $maxTries . ' tries');
    }
}

/** @ODM\Document */
class GH1017Document
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedOne(targetDocument=GH1017EmbeddedDocument::class)
     *
     * @var GH1017EmbeddedDocument|null
     */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class GH1017EmbeddedDocument
{
}
