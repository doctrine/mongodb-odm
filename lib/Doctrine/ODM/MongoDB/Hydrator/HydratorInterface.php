<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Hydrator;

/**
 * The HydratorInterface defines methods all hydrator need to implement
 *
 * @psalm-import-type Hints from UnitOfWork
 */
interface HydratorInterface
{
    /**
     * Hydrate array of MongoDB document data into the given document object.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     *
     * @psalm-param Hints $hints
     */
    public function hydrate(object $document, array $data, array $hints = []): array;
}
