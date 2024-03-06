<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'strategy')]
class Strategy
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string[] */
    #[ODM\Field(type: 'collection')]
    public $logs = [];

    /** @var Collection<int, Message>|array<Message> */
    #[ODM\EmbedMany(targetDocument: Message::class, strategy: 'set')]
    public $messages = [];

    /** @var Collection<int, Task>|array<Task> */
    #[ODM\ReferenceMany(targetDocument: Task::class, strategy: 'set')]
    public $tasks = [];
}
