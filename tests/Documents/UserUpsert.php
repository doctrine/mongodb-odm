<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'users_upsert')]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
#[ODM\DiscriminatorField('discriminator')]
#[ODM\DiscriminatorMap(['user' => 'Documents\UserUpsert', 'child' => 'Documents\UserUpsertChild'])]
class UserUpsert
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $username;

    /** @var int|null */
    #[ODM\Field(type: 'int')]
    public $hits;

    /** @var int|null */
    #[ODM\Field(type: 'int', strategy: 'increment')]
    public $count;

    /** @var Collection<int, Group> */
    #[ODM\ReferenceMany(targetDocument: Group::class, cascade: ['all'])]
    public $groups;

    /** @var string|null */
    #[ODM\Field(type: 'string', nullable: true)]
    public $nullableField;
}
