<?php

namespace Documents\Functional;

/** @Document(collection="embedded_test") */
class EmbeddedTestLevel0b
{
    /** @Id */
    public $id;
    /** @String */
    public $name;
    /** @EmbedOne(targetDocument="EmbeddedTestLevel1") */
    public $oneLevel1;
    /** @EmbedMany(targetDocument="EmbeddedTestLevel1") */
    public $level1 = array();
}
