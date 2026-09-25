<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\ChangeSets;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * One document's input to {@see ChangeSetComputer::computeChangeSets()}.
 *
 * @internal
 */
final class ChangeSetComputationRequest
{
    /**
     * @param ClassMetadata<T>          $class
     * @param T                         $document
     * @param array<string, mixed>|null $originalData null means the document has no snapshot yet (i.e. it is new)
     * @param array<string, mixed>      $actualData   the document's current in-memory field values
     *
     * @template T of object = object
     */
    public function __construct(
        public readonly ClassMetadata $class,
        public readonly object $document,
        public readonly ?array $originalData,
        public readonly array $actualData,
        public readonly ?ChangeSet $existingChangeSet,
        public readonly bool $isChangeTrackingNotify,
        public readonly bool $forceRecompute,
    ) {
    }
}
