<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="strategy") */
class Strategy
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="collection")
     *
     * @var string[]
     */
    public $logs = [];

    /**
     * @ODM\EmbedMany(targetDocument=Message::class, strategy="set")
     *
     * @var Collection<int, Message>|array<Message>
     */
    public $messages = [];

    /**
     * @ODM\ReferenceMany(targetDocument=Task::class, strategy="set")
     *
     * @var Collection<int, Task>|array<Task>
     */
    public $tasks = [];
}
