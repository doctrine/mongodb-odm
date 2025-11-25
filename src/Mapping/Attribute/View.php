<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
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

    public function __construct(
        ?string $db = null,
        ?string $view = null,
        ?string $rootClass = null,
        ?string $repositoryClass = null,
    ) {
        $this->db              = $db;
        $this->view            = $view;
        $this->rootClass       = $rootClass;
        $this->repositoryClass = $repositoryClass;
    }
}

// @phpstan-ignore class.notFound
class_alias(View::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\View::class);
