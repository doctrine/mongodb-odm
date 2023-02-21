<?php

declare(strict_types=1);

namespace Documents\CustomRepository;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(repositoryClass="Documents\CustomRepository\Repository") */
class Document
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;
}
