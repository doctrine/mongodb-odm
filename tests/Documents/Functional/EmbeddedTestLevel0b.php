<?php

namespace Documents\Functional;

/** @Document(collection="embedded_test") */
class EmbeddedTestLevel0b extends EmbeddedTestLevel0
{
    /** @EmbedOne(targetDocument="EmbeddedTestLevel1") */
    public $oneLevel1;
}
