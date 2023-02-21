<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function get_class;

class GH944Test extends BaseTest
{
    public function testIssue(): void
    {
        $d = new GH944Document();
        $d->data->add(new GH944Embedded('1'));
        $d->data->add(new GH944Embedded('2'));
        $this->dm->persist($d);
        $this->dm->flush();
        $this->dm->clear();

        $d = $this->dm->find(get_class($d), $d->id);
        self::assertCount(2, $d->data);
        $d->removeByText('1');
        self::assertCount(1, $d->data);
        $this->dm->flush();

        $d = $this->dm->find(get_class($d), $d->id);
        self::assertCount(1, $d->data);
        $d->removeByText('2');
        self::assertEmpty($d->data);
        $this->dm->flush();
        $this->dm->clear();

        $d = $this->dm->find(get_class($d), $d->id);
        self::assertEmpty($d->data);
    }
}

/** @ODM\Document */
class GH944Document
{
    /**
     * @ODM\Id(strategy="auto")
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedMany
     *
     * @var Collection<int, GH944Embedded>
     */
    public $data;

    public function __construct()
    {
        $this->data = new ArrayCollection();
    }

    public function removeByText(string $text): void
    {
        foreach ($this->data as $d) {
            if ($d->text !== $text) {
                continue;
            }

            $this->data->removeElement($d);
        }
    }
}

/** @ODM\EmbeddedDocument */
class GH944Embedded
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }
}
