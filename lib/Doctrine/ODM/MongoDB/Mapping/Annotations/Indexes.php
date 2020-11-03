<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Specifies a list of indexes for a document
 *
 * @deprecated class was deprecated in doctrine/mongodb-odm 2.2 and will be removed in 3.0. Specify all Index and UniqueIndex annotations on a class level.
 *
 * @Annotation
 */
final class Indexes extends Annotation
{
}
