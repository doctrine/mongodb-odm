<?php

namespace Documents\Functional;

/** @Document(collection="functional_tests") */
class SimpleEmbedAndReference
{
    /** @Id */
    public $id;

    /** @EmbedMany(targetDocument="Reference", cascade={"all"}) */
    public $embedMany = array();

    /** @ReferenceMany(targetDocument="Embedded") */
    public $referenceMany = array();

    /** @EmbedOne(targetDocument="Reference", cascade={"all"}) */
    public $embedOne;

    /** @ReferenceOne(targetDocument="Embedded") */
    public $referenceOne;
}