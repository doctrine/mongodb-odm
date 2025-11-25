<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\EmbeddedDocument]
class SubCategory extends BaseCategory
{
}
