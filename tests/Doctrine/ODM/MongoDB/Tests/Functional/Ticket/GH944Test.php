<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH944Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testIssue()
    {
        $d = new GH944Document();
        $d->data->add(new GH944Embedded('1'));
        $d->data->add(new GH944Embedded('2'));
        $this->dm->persist($d);
        $this->dm->flush();
        $this->dm->clear();

        $d = $this->dm->find(get_class($d), $d->id);
        $this->assertCount(2, $d->data);
        $d->removeByText('1');
        $this->assertCount(1, $d->data);
        $this->dm->flush();

        $d = $this->dm->find(get_class($d), $d->id);
        $this->assertCount(1, $d->data);
        $d->removeByText('2');
        $this->assertCount(0, $d->data);
        $this->dm->flush();
        $this->dm->clear();

        $d = $this->dm->find(get_class($d), $d->id);
        $this->assertCount(0, $d->data);
    }
}

/**
 * @ODM\Document
 */
class GH944Document
{
    /** @ODM\Id(strategy="auto") */
    public $id;

    /** @ODM\EmbedMany */
    public $data;

    public function __construct()
    {
        $this->data = new ArrayCollection();
    }

    public function removeByText($text)
    {
        foreach ($this->data as $d) {
            if ($d->text === $text) {
                $this->data->removeElement($d);
            }
        }
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class GH944Embedded
{
    /** @ODM\Field(type="string") */
    public $text;

    public function __construct($text)
    {
        $this->text = $text;
    }
}
