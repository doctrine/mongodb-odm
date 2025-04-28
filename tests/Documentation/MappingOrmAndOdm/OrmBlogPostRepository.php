<?php

declare(strict_types=1);

namespace Documentation\MappingOrmAndOdm;

use Doctrine\ORM\EntityRepository;

/** @extends EntityRepository<BlogPost> */
final class OrmBlogPostRepository extends EntityRepository implements BlogPostRepositoryInterface
{
    public function findPostById(int $id): ?BlogPost
    {
        return $this->findOneBy(['id' => $id]);
    }
}
