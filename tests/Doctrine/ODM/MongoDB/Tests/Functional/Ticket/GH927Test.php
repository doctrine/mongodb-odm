<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH927Test extends BaseTestCase
{
    public function testInheritedClassHasAssociationMapping(): void
    {
        $parentMetadata = $this->dm->getClassMetadata(GH927Parent::class);
        self::assertArrayHasKey('reference', $parentMetadata->associationMappings);

        $childMetadata = $this->dm->getClassMetadata(GH927Child::class);
        self::assertArrayHasKey('reference', $childMetadata->associationMappings);
    }
}

/** @ODM\Document */
class GH927Parent
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\ReferenceOne(targetDocument=Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH927Reference::class)
     *
     * @var GH927Reference|null
     */
    protected $reference;
}

/** @ODM\Document */
class GH927Child extends GH927Parent
{
}

/** @ODM\Document */
class GH927Reference
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;
}
