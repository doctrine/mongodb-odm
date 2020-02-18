<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/** @Annotation */
final class View extends AbstractDocument
{
    /** @var string|null */
    public $db;

    /** @var string|null */
    public $view;

    /** @var string|null */
    public $rootClass;

    /** @var string|null */
    public $repositoryClass;
}
