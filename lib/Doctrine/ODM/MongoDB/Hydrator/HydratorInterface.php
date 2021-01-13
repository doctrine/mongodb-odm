<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Hydrator;

/**
 * The HydratorInterface defines methods all hydrator need to implement
 */
interface HydratorInterface
{
    /**
     * Hydrate array of MongoDB document data into the given document object.
     */
    public function hydrate(object $document, array $data, array $hints = []): array;
}
