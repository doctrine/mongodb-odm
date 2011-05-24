<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="agents") */
class Agent
{
    /** @ODM\Id */
    public $id;

    /**
     * @ODM\ReferenceOne(discriminatorMap={
     * "server"="Server",
     * "server_guest"="GuestServer"
     * })
     */
    public $server;
}