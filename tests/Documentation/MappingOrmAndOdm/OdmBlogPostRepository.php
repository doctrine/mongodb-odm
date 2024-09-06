<?php

declare(strict_types=1);

namespace Documentation\MappingOrmAndOdm;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

final class OdmBlogPostRepository extends DocumentRepository implements BlogPostRepositoryInterface
{
    public function findPostById(int $id): ?BlogPost
    {
        return $this->findOneBy(['id' => $id]);
    }
}
