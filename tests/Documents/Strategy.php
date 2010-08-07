<?php

namespace Documents;

/** @Document */
class Strategy
{
    /** @Id */
    public $id;

    /** @Collection(strategy="set") */
    public $logs = array();

    /** @EmbedMany(targetDocument="Message", strategy="set") */
    public $messages = array();

    /** @ReferenceMany(targetDocument="Task", strategy="set") */
    public $tasks = array();
}