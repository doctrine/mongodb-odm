<?php

declare(strict_types=1);

namespace Documentation\Introduction;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Project
{
    #[ODM\Id]
    public string $id;

    public function __construct(
        #[ODM\Field]
        public string $name,
    ) {
    }
}
