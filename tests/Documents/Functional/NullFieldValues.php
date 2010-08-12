<?php

namespace Documents\Functional;

/** @Document(collection="functional_tests") */
class NullFieldValues
{
    /** @Id */
    public $id;

    /** @Field(nullable=true) */
    public $field;
}