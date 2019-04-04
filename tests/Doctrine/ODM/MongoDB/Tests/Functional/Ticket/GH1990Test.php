<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1990Test extends BaseTest
{
    public function testInitialisationOfInverseProxy() : void
    {
        // Generate proxy class using generateProxyClasses to ensure it is
        // consistent with other proxy classes
        $metadata = $this->dm->getClassMetadata(GH1990Document::class);
        $this->dm->getProxyFactory()->generateProxyClasses([$metadata]);

        $parent = new GH1990Document(null);
        $child  = new GH1990Document($parent);
        $this->dm->persist($parent);
        $this->dm->persist($child);

        $this->dm->flush();
        $this->dm->clear();

        $this->dm->find(GH1990Document::class, $child->getId());

        self::assertInstanceOf(GH1990Document::class, $child);
    }
}

/** @ODM\Document */
class GH1990Document
{
    /** @ODM\Id */
    private $id;

    /** @ODM\ReferenceOne(targetDocument=GH1990Document::class) */
    private $parent;

    public function __construct(?GH1990Document $parent)
    {
        $this->parent = $parent;
    }

    public function getId() : ?string
    {
        return $this->id;
    }
}
