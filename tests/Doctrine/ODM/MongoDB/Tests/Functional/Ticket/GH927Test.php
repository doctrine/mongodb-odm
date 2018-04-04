<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH927Test extends BaseTest
{
    public function testInheritedClassHasAssociationMapping()
    {
        $parentMetadata = $this->dm->getClassMetadata(GH927Parent::class);
        $this->assertArrayHasKey('reference', $parentMetadata->associationMappings);

        $childMetadata = $this->dm->getClassMetadata(GH927Child::class);
        $this->assertArrayHasKey('reference', $childMetadata->associationMappings);
    }
}

/** @ODM\Document */
class GH927Parent
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\ReferenceOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH927Reference") */
    protected $reference;
}

/** @ODM\Document */
class GH927Child extends GH927Parent
{
}

/** @ODM\Document */
class GH927Reference
{
    /** @ODM\Id */
    protected $id;
}
