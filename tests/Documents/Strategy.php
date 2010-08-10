<?php

namespace Documents;

/** @Document(collection="strategy") */
class Strategy
{
    /** @Id */
    public $id;

    /** @Collection(strategy="set") */
    public $logs = array();

    /** @EmbedMany(targetDocument="Message", strategy="set", cascade={"all"}) */
    public $messages = array();

    /** @ReferenceMany(targetDocument="Task", strategy="set") */
    public $tasks = array();
}