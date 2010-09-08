<?php

namespace Documents\Functional;

/** @EmbeddedDocument */
class EmbeddedTestLevel1
{
    /** @String */
    public $name;
    /** @EmbedMany(targetDocument="EmbeddedTestLevel2") */
    public $level2 = array();
}