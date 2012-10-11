<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH426Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $form = new GH426Form();
        $form->fields[] = new GH426Field($form);
        $form->fields[] = new GH426Field($form);

        $this->dm->persist($form);
        $this->dm->flush();
        $this->dm->clear();

        $form = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH426Form', $form->id);

        $this->assertEquals(2, $form->fields->count());
        $this->assertSame($form->fields[0], $form->firstField);
        $this->assertSame($form->fields[1], $form->lastField);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH426Field', $form->firstField);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH426Field', $form->lastField);
    }
}

/** @ODM\Document */
class GH426Form
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceMany(targetDocument="GH426Field", mappedBy="form", cascade={"all"}) */
    public $fields = array();

    /** @ODM\ReferenceOne(targetDocument="GH426Field", mappedBy="form", sort={"_id":1}) */
    public $firstField;

    /** @ODM\ReferenceOne(targetDocument="GH426Field", mappedBy="form", sort={"_id":-1}) */
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
