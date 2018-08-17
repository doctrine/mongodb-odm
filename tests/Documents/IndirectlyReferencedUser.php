<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class IndirectlyReferencedUser
{

    /**
     * @var User
     *
     * @ODM\ReferenceOne(targetDocument="Documents\User", storeAs="ref")
     */
    public $user;
}
