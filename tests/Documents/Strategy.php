<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="strategy") */
class Strategy
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Collection() */
    public $logs = [];

    /** @ODM\EmbedMany(targetDocument="Message", strategy="set") */
    public $messages = [];

    /** @ODM\ReferenceMany(targetDocument="Task", strategy="set") */
    public $tasks = [];
}
