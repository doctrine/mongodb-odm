<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Deliberately not listed in Project's discriminator map, to exercise
 * PersistenceBuilder's handling of an unmapped discriminated class.
 */
#[ODM\Document]
class UnmappedSubProject extends Project
{
}
