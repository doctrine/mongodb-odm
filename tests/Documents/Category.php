<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Category extends BaseCategory
{
    /** @ODM\Id */
    private $id;
}