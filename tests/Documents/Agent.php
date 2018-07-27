<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="agents") */
class Agent
{
    /** @ODM\Id */
    public $id;

    /**
     * @ODM\ReferenceOne(discriminatorMap={
     * "server"=Server::class,
     * "server_guest"=GuestServer::class
     * })
     */
    public $server;
}
