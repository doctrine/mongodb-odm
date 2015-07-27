<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH927Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testInheritedClassHasAssociationMapping()
    {
        $parentMetadata = $this->dm->getClassMetadata('Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH927Parent');
        $this->assertArrayHasKey('reference', $parentMetadata->associationMappings);

        $childMetadata = $this->dm->getClassMetadata('Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH927Child');
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
