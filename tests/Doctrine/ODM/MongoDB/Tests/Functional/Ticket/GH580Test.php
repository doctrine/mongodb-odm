<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\Driver\Exception\BulkWriteException;

class GH580Test extends BaseTest
{
    public function testDocumentPersisterShouldClearQueuedInsertsOnMongoException()
    {
        $class = GH580Document::class;

        $schemaManager = $this->dm->getSchemaManager();
        $schemaManager->updateDocumentIndexes($class);

        $repository = $this->dm->getRepository($class);

        $this->assertCount(0, $repository->findAll());

        // Create, persist and flush initial object
        $doc1 = new GH580Document();
        $doc1->name = 'foo';

        $this->dm->persist($doc1);
        $this->dm->flush();
        $this->dm->clear($class);

        // Create, persist and flush a second, duplicate object
        $doc2 = new GH580Document();
        $doc2->name = 'foo';
        $this->dm->persist($doc2);

        try {
            $this->dm->flush();
            $this->fail('Expected BulkWriteException for duplicate value');
        } catch (BulkWriteException $e) {
        }

        $this->dm->clear($class);

        // Remove initial object
        $doc1 = $repository->findOneBy(['name' => 'foo']);
        $this->dm->remove($doc1) ;
        $this->dm->flush();
        $this->dm->clear($class);

        // Create a third object
        $doc3 = new GH580Document();
        $doc3->name = 'bar';
        $this->dm->persist($doc3);
        $this->dm->flush();
        $this->dm->clear($class);

        /* Repository should contain one object, but may contain two if the
         * DocumentPersister was not cleaned up.
         */
        $this->assertCount(1, $repository->findAll());
    }
}

/** @ODM\Document */
class GH580Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") @ODM\Index(unique=true) */
    public $name;
}
