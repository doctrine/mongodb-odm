<?php

declare(strict_types=1);

namespace Documentation\MappingOrmAndOdm;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrmBlogPostRepository::class)]
#[ORM\Table(name: 'blog_posts')]
#[ODM\Document(repositoryClass: OdmBlogPostRepository::class)]
class BlogPost
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
    public int $id;

    #[ORM\Column(type: 'string')]
    #[ODM\Field]
    public string $title;

    #[ORM\Column(type: 'text')]
    #[ODM\Field]
    public string $body;
}
