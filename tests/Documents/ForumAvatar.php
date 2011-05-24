<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class ForumAvatar
{
    /** @ODM\Id */
    public $id;
}
