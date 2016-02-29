<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="users_upsert_id_strategy_none")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="discriminator")
 * @ODM\DiscriminatorMap({
 *     "user"="Documents\UserUpsertIdStrategyNone"
 * })
 */
class UserUpsertIdStrategyNone
{
    /** @ODM\Id(strategy="none") */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;

    /** @ODM\Field(type="int") */
    public $hits;

    /** @ODM\Field(type="int", strategy="increment") */
    public $count;

    /** @ODM\ReferenceMany(targetDocument="Group", cascade={"all"}) */
    public $groups;
}
