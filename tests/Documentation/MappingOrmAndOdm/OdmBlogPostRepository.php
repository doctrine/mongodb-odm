<?php

declare(strict_types=1);

namespace Documentation\MappingOrmAndOdm;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/** @extends DocumentRepository<BlogPost> */
final class OdmBlogPostRepository extends DocumentRepository implements BlogPostRepositoryInterface
{
    public function findPostById(int $id): ?BlogPost
    {
        return $this->findOneBy(['id' => $id]);
    }
}
