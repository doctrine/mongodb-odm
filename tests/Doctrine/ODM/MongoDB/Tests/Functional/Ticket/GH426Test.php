<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH426Test extends BaseTest
{
    public function testTest()
    {
        $form = new GH426Form();
        $form->fields[] = new GH426Field($form);
        $form->fields[] = new GH426Field($form);

        $this->dm->persist($form);
        $this->dm->flush();
        $this->dm->clear();

        $form = $this->dm->find(GH426Form::class, $form->id);

        $this->assertEquals(2, $form->fields->count());
        $this->assertSame($form->fields[0], $form->firstField);
        $this->assertSame($form->fields[1], $form->lastField);
        $this->assertInstanceOf(GH426Field::class, $form->firstField);
        $this->assertInstanceOf(GH426Field::class, $form->lastField);
    }
}

/** @ODM\Document */
class GH426Form
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceMany(targetDocument=GH426Field::class, mappedBy="form", cascade={"all"}) */
    public $fields = [];

    /** @ODM\ReferenceOne(targetDocument=GH426Field::class, mappedBy="form", sort={"_id":1}) */
    public $firstField;

    /** @ODM\ReferenceOne(targetDocument=GH426Field::class, mappedBy="form", sort={"_id":-1}) */
    public $lastField;
}

/** @ODM\Document */
class GH426Field
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(inversedBy="fields", discriminatorMap={"f":"GH426Form"}, discriminatorField="type", cascade={"all"}) */
    public $form;

    public function __construct(GH426Form $form)
    {
        $this->form = $form;
    }
}
