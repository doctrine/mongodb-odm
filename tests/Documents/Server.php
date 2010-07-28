<?php

namespace Documents;

/**
 * @Document(collection="servers")
 * @InheritanceType("SINGLE_COLLECTION")
 * @DiscriminatorField(fieldName="stype")
 * @DiscriminatorMap({
 * "server"="Server",
 * "server_guest"="GuestServer"
 * })
 */
class Server
{
    /** @Id */
    public $id;

    /** @String */
    public $name;
}