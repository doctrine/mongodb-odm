<?php

namespace Documents\Functional;

/** @EmbeddedDocument */
class EmbeddedTestLevel1
{
    /** @String */
    public $name;
    /** @EmbedMany(targetDocument="EmbeddedTestLevel2", cascade={"all"}) */
    public $level2 = array();
}