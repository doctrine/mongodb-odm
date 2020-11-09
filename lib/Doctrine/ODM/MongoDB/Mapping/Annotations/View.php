<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/** @Annotation */
final class View extends AbstractDocument implements NamedArgumentConstructorAnnotation
{
    /** @var string|null */
    public $db;

    /** @var string|null */
    public $view;

    /** @var string|null */
    public $rootClass;

    /** @var string|null */
    public $repositoryClass;

    public function __construct(
        ?string $db = null,
        ?string $view = null,
        ?string $rootClass = null,
        ?string $repositoryClass = null
    ) {
        $this->db = $db;
        $this->view = $view;
        $this->rootClass = $rootClass;
        $this->repositoryClass = $repositoryClass;
    }
}
