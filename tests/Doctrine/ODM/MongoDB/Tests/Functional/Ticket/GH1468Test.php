<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Documents;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1468Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFilesCollectionIsDroppedProperly()
    {
        $file = new Documents\File();
        $this->dm->persist($file);
        $this->dm->flush();
        $this->assertCount(1, $this->dm->getRepository(get_class($file))->findAll());

        $this->dm->getSchemaManager()->dropCollections();
        $this->assertCount(0, $this->dm->getRepository(get_class($file))->findAll());
    }
}
