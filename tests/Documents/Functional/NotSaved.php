<?php

namespace Documents\Functional;

/** @Document(collection="functional_tests") */
class NotSaved
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @NotSaved */
    public $notSaved;
}