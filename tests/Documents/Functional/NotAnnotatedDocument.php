<?php

namespace Documents\Functional;

/** @Document(collection="functional_tests") */
class NotAnnotatedDocument
{
    /** @Field */
    public $field;

    public $transientField;
}