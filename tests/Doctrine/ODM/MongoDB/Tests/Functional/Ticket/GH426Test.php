<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH426Test extends BaseTestCase
{
    public function testTest(): void
    {
        $form           = new GH426Form();
        $form->fields[] = new GH426Field($form);
        $form->fields[] = new GH426Field($form);

        $this->dm->persist($form);
        $this->dm->flush();
        $this->dm->clear();

        $form = $this->dm->find(GH426Form::class, $form->id);

        self::assertEquals(2, $form->fields->count());
        self::assertSame($form->fields[0], $form->firstField);
        self::assertSame($form->fields[1], $form->lastField);
        self::assertInstanceOf(GH426Field::class, $form->firstField);
        self::assertInstanceOf(GH426Field::class, $form->lastField);
    }
}

/** @ODM\Document */
class GH426Form
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceMany(targetDocument=GH426Field::class, mappedBy="form", cascade={"all"})
     *
     * @var Collection<int, GH426Field>|array<GH426Field>
     */
    public $fields = [];

    /**
     * @ODM\ReferenceOne(targetDocument=GH426Field::class, mappedBy="form", sort={"_id":1})
     *
     * @var GH426Field|null
     */
    public $firstField;

    /**
     * @ODM\ReferenceOne(targetDocument=GH426Field::class, mappedBy="form", sort={"_id":-1})
     *
     * @var GH426Field|null
     */
    public $lastField;
}

/** @ODM\Document */
class GH426Field
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(inversedBy="fields", discriminatorMap={"f":GH426Form::class}, discriminatorField="type", cascade={"all"})
     *
     * @var GH426Form
     */
    public $form;

    public function __construct(GH426Form $form)
    {
        $this->form = $form;
    }
}
