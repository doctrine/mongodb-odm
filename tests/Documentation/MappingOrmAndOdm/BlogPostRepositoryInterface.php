<?php

declare(strict_types=1);

namespace Documentation\MappingOrmAndOdm;

interface BlogPostRepositoryInterface
{
    public function findPostById(int $id): ?BlogPost;
}
