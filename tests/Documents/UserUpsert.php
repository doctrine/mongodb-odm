<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="users_upsert")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("discriminator")
 * @ODM\DiscriminatorMap({
 *     "user"="Documents\UserUpsert",
 *     "child"="Documents\UserUpsertChild"
 * })
 */
class UserUpsert
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\Field(type="int")
     *
     * @var int|null
     */
    public $hits;

    /**
     * @ODM\Field(type="int", strategy="increment")
     *
     * @var int|null
     */
    public $count;

    /**
     * @ODM\ReferenceMany(targetDocument=Group::class, cascade={"all"})
     *
     * @var Collection<int, Group>
     */
    public $groups;

    /**
     * @ODM\Field(type="string", nullable=true)
     *
     * @var string|null
     */
    public $nullableField;
}
