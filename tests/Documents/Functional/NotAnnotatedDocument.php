<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="functional_tests") */
class NotAnnotatedDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field */
    public $field;

    public $transientField;
}