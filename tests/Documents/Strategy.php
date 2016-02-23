<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="strategy") */
class Strategy
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="collection") */
    public $logs = array();

    /** @ODM\EmbedMany(targetDocument="Message", strategy="set") */
    public $messages = array();

    /** @ODM\ReferenceMany(targetDocument="Task", strategy="set") */
    public $tasks = array();
}
