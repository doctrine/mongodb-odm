<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH977Test extends BaseTestCase
{
    public function testAutoRecompute(): void
    {
        $d         = new GH977TestDocument();
        $d->value1 = 'Value 1';
        $this->dm->persist($d);
        $this->dm->flush();
        $this->dm->clear();

        $d         = $this->dm->getRepository($d::class)->findOneBy(['value1' => 'Value 1']);
        $d->value1 = 'Changed';
        $this->uow->computeChangeSet($this->dm->getClassMetadata($d::class), $d);
        $changeSet = $this->uow->getDocumentChangeSet($d);
        if (isset($changeSet['value1'])) {
            $d->value2 = 'v1 has changed';
        }

        $this->dm->flush();
        $this->dm->clear();

        $d = $this->dm->getRepository($d::class)->findOneBy(['value1' => 'Changed']);
        self::assertNotNull($d);
        self::assertEquals('v1 has changed', $d->value2);
    }

    public function testRefreshClearsChangeSet(): void
    {
        $d         = new GH977TestDocument();
        $d->value1 = 'Value 1';
        $this->dm->persist($d);
        $this->dm->flush();
        $this->dm->clear();

        $d         = $this->dm->getRepository($d::class)->findOneBy(['value1' => 'Value 1']);
        $d->value1 = 'Changed';
        $this->uow->computeChangeSet($this->dm->getClassMetadata($d::class), $d);
        $this->dm->refresh($d);
        $this->dm->flush();
        $this->dm->clear();

        $d = $this->dm->getRepository($d::class)->findOneBy(['value1' => 'Value 1']);
        self::assertNotNull($d);
    }
}

#[ODM\Document]
class GH977TestDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $value1;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $value2;
}
