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

#[ODM\Document]
class GH426Form
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var Collection<int, GH426Field>|array<GH426Field> */
    #[ODM\ReferenceMany(targetDocument: GH426Field::class, mappedBy: 'form', cascade: ['all'])]
    public $fields = [];

    /** @var GH426Field|null */
    #[ODM\ReferenceOne(targetDocument: GH426Field::class, mappedBy: 'form', sort: ['_id' => 1])]
    public $firstField;

    /** @var GH426Field|null */
    #[ODM\ReferenceOne(targetDocument: GH426Field::class, mappedBy: 'form', sort: ['_id' => -1])]
    public $lastField;
}

#[ODM\Document]
class GH426Field
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var GH426Form */
    #[ODM\ReferenceOne(inversedBy: 'fields', discriminatorMap: ['f' => GH426Form::class], discriminatorField: 'type', cascade: ['all'])]
    public $form;

    public function __construct(GH426Form $form)
    {
        $this->form = $form;
    }
}
