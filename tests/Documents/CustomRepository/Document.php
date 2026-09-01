<?php

declare(strict_types=1);

namespace Documents\CustomRepository;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document(repositoryClass: 'Documents\CustomRepository\Repository')]
class Document
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;
}
