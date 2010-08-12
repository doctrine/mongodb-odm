<?php

namespace Documents\Functional;

/** @Document(collection="functional_tests") */
class NotAnnotatedDocument
{
    /** @Id */
    public $id;

    /** @Field */
    public $field;

    public $transientField;
}