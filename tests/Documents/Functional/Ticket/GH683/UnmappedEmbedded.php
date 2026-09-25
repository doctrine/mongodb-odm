<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\GH683;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Deliberately not listed in AbstractEmbedded's discriminator map, to
 * exercise PersistenceBuilder's handling of an unmapped discriminated class.
 */
#[ODM\EmbeddedDocument]
class UnmappedEmbedded extends AbstractEmbedded
{
}
