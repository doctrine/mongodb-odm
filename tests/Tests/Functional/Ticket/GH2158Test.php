<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH2158Test extends BaseTestCase
{
    public function testDiscriminatorMapCreationType(): void
    {
        $obj = new GH2158FirstType();
        $this->dm->persist($obj);
        $this->dm->flush();

        self::assertEquals($this->dm->find(GH2158Abstract::class, $obj->getId()), $obj);
    }
}

#[ODM\Document(collection: 'documents')]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
#[ODM\DiscriminatorField('type')]
#[ODM\DiscriminatorMap([0 => GH2158FirstType::class, 1 => GH2158SecondType::class])]
abstract class GH2158Abstract
{
    /** @var string */
    #[ODM\Id]
    protected $id;

    public function getId(): string
    {
        return $this->id;
    }
}

#[ODM\Document]
class GH2158FirstType extends GH2158Abstract
{
}

#[ODM\Document]
class GH2158SecondType extends GH2158Abstract
{
}
