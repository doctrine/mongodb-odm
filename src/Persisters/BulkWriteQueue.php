<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Persisters;

use Closure;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Driver\Exception\BulkWriteException;

use function array_map;
use function array_values;
use function count;
use function spl_object_id;

/**
 * Accumulator that collects per-collection bulkWrite operations during a
 * single {@see \Doctrine\ODM\MongoDB\UnitOfWork::doCommit()} pass.
 *
 * Each entry pushed via {@see addInsertOne()}, {@see addUpdateOne()} or
 * {@see addDeleteOne()} is queued in insertion order against the supplied
 * {@see Collection} instance. {@see flush()} then drains every pending
 * collection with a single {@see Collection::bulkWrite()} call.
 *
 * Per-op callbacks (e.g. for `LockException` detection on versioned docs or
 * insertedId bind-back) are invoked synchronously after the bulkWrite for
 * the owning collection returns.
 *
 * @internal
 *
 * @phpstan-type OnInsertResult Closure(?ObjectId): void
 * @phpstan-type OnUpdateResult Closure(bool, bool, ?ObjectId): void
 * @phpstan-type OnDeleteResult Closure(int): void
 * @phpstan-type PendingOp array{
 *     type: 'insertOne'|'updateOne'|'deleteOne',
 *     op: array<string, mixed>,
 *     document: object,
 *     onResult: ?Closure,
 * }
 */
final class BulkWriteQueue
{
    /**
     * Keyed by {@see spl_object_id()} of the target Collection instance.
     *
     * @var array<int, Collection>
     */
    private array $collections = [];

    /**
     * Keyed by {@see spl_object_id()} of the target Collection instance, then
     * by insertion index (0..n).
     *
     * @var array<int, list<PendingOp>>
     */
    private array $pending = [];

    /**
     * Per-collection bulkWrite options accumulator. Each addInsertOne /
     * addUpdateOne / addDeleteOne call provides the options that should be
     * forwarded to the per-collection {@see Collection::bulkWrite()} call;
     * the first non-empty option set wins so callers within a single class
     * loop end up with their write concern / session / etc. applied.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $bulkOptions = [];

    public function hasPending(): bool
    {
        foreach ($this->pending as $ops) {
            if ($ops !== []) {
                return true;
            }
        }

        return false;
    }

    public function clear(): void
    {
        $this->collections = [];
        $this->pending     = [];
        $this->bulkOptions = [];
    }

    /**
     * Queue an `insertOne` op against $collection.
     *
     * @param array<string, mixed> $document       The BSON-ready insert payload.
     * @param array<string, mixed> $bulkOptions    Options to forward to the per-collection
     *                                            {@see Collection::bulkWrite()} call (e.g.
     *                                            `writeConcern`, `session`). The first non-empty
     *                                            set per collection is kept; passing the same
     *                                            options for every op in the same class loop is
     *                                            the expected use.
     * @param object               $sourceDocument The owning PHP document instance.
     * @param Closure|null         $onResult       Invoked as `fn(?\MongoDB\BSON\ObjectId $insertedId): void`
     *                                            after the bulkWrite for this collection returns.
     *
     * @return int Position of the op within this collection's pending batch.
     */
    public function addInsertOne(Collection $collection, array $document, array $bulkOptions, object $sourceDocument, ?Closure $onResult = null): int
    {
        return $this->push($collection, $bulkOptions, [
            'type' => 'insertOne',
            'op' => ['insertOne' => [$document]],
            'document' => $sourceDocument,
            'onResult' => $onResult,
        ]);
    }

    /**
     * Queue an `updateOne` op against $collection.
     *
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $update
     * @param array<string, mixed> $opOptions   Driver-level updateOne options (e.g. ['upsert' => true]).
     *                                          Only options that are valid for `bulkWrite` per-op are forwarded.
     * @param array<string, mixed> $bulkOptions Bulk-level options (see {@see addInsertOne()}).
     * @param Closure|null         $onResult    Invoked as
     *                                          `fn(int $matchedCount, int $modifiedCount, ?\MongoDB\BSON\ObjectId $upsertedId): void`
     *                                          after the bulkWrite for this collection returns.
     */
    public function addUpdateOne(
        Collection $collection,
        array $filter,
        array $update,
        array $opOptions,
        array $bulkOptions,
        object $sourceDocument,
        ?Closure $onResult = null,
    ): int {
        $op = ['updateOne' => [$filter, $update]];
        if ($opOptions !== []) {
            $op['updateOne'][] = $opOptions;
        }

        return $this->push($collection, $bulkOptions, [
            'type' => 'updateOne',
            'op' => $op,
            'document' => $sourceDocument,
            'onResult' => $onResult,
        ]);
    }

    /**
     * Queue a `deleteOne` op against $collection.
     *
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $opOptions
     * @param array<string, mixed> $bulkOptions Bulk-level options (see {@see addInsertOne()}).
     * @param Closure|null         $onResult    Invoked as `fn(int $deletedCount): void`
     *                                          after the bulkWrite for this collection returns.
     */
    public function addDeleteOne(
        Collection $collection,
        array $filter,
        array $opOptions,
        array $bulkOptions,
        object $sourceDocument,
        ?Closure $onResult = null,
    ): int {
        $op = ['deleteOne' => [$filter]];
        if ($opOptions !== []) {
            $op['deleteOne'][] = $opOptions;
        }

        return $this->push($collection, $bulkOptions, [
            'type' => 'deleteOne',
            'op' => $op,
            'document' => $sourceDocument,
            'onResult' => $onResult,
        ]);
    }

    /**
     * Flush every pending collection's queued ops as one bulkWrite per collection.
     *
     * Within a collection ops are issued with `ordered: true` so the server
     * stops at the first failure. On {@see BulkWriteException} the entire
     * queue is cleared (for both successful and failed collections) and the
     * exception is re-raised — callers must mark documents as still
     * scheduled if that is the desired behavior.
     *
     * @param array<string, mixed> $options Caller-supplied bulk-level options
     *                                      (e.g. `session`). These are merged
     *                                      on top of the per-collection options
     *                                      captured at push time.
     */
    public function flush(array $options = []): void
    {
        if (! $this->hasPending()) {
            return;
        }

        try {
            foreach ($this->collections as $oid => $collection) {
                $pending = $this->pending[$oid] ?? [];
                if ($pending === []) {
                    continue;
                }

                // Reset before issuing so that a thrown exception leaves no stale ops in the queue.
                $this->pending[$oid] = [];

                $perCollectionOptions = $this->bulkOptions[$oid] ?? [];
                // Caller-supplied options take precedence over the per-collection options
                // captured at push time (e.g. an enclosing transaction's session always wins).
                $bulkOptions = ['ordered' => true] + $options + $perCollectionOptions;

                $ops    = array_values(array_map(static fn (array $entry): array => $entry['op'], $pending));
                $result = $collection->bulkWrite($ops, $bulkOptions);

                $insertedIds = $result->getInsertedIds();
                $upsertedIds = $result->getUpsertedIds();

                $matchedTotal  = $result->getMatchedCount();
                $modifiedTotal = $result->getModifiedCount();
                $deletedTotal  = $result->getDeletedCount();

                foreach ($pending as $opIndex => $entry) {
                    if ($entry['onResult'] === null) {
                        continue;
                    }

                    switch ($entry['type']) {
                        case 'insertOne':
                            $insertedId = $insertedIds[$opIndex] ?? null;
                            ($entry['onResult'])($insertedId);
                            break;

                        case 'updateOne':
                            $upsertedId = $upsertedIds[$opIndex] ?? null;
                            // Per-op counts are not exposed by the driver. The queue only attaches
                            // an onResult callback to ops that are *individually* fenced (versioned
                            // or lockable documents), and the UnitOfWork ensures those land in their
                            // own bulkWrite via a single-op batch. We therefore pass the aggregate
                            // matched/modified counts; for a single-op batch they are the per-op
                            // counts.
                            ($entry['onResult'])($matchedTotal, $modifiedTotal, $upsertedId);
                            break;

                        case 'deleteOne':
                            // Same single-op fencing applies for delete callbacks.
                            ($entry['onResult'])($deletedTotal);
                            break;
                    }
                }
            }
        } catch (BulkWriteException $e) {
            // Drop every pending op across all collections so the next attempt starts clean.
            $this->pending     = [];
            $this->collections = [];
            $this->bulkOptions = [];

            throw $e;
        }

        // Successful drain — reset collection bookkeeping for the next phase / commit.
        $this->collections = [];
        $this->pending     = [];
        $this->bulkOptions = [];
    }

    /**
     * @param array<string, mixed> $bulkOptions
     * @param PendingOp            $entry
     */
    private function push(Collection $collection, array $bulkOptions, array $entry): int
    {
        $oid = spl_object_id($collection);

        if (! isset($this->collections[$oid])) {
            $this->collections[$oid] = $collection;
            $this->pending[$oid]     = [];
            $this->bulkOptions[$oid] = $bulkOptions;
        } elseif ($bulkOptions !== [] && $this->bulkOptions[$oid] === []) {
            // First push for this collection didn't carry options; adopt the next non-empty
            // set so the per-collection bulkWrite still respects per-class write concerns.
            $this->bulkOptions[$oid] = $bulkOptions;
        }

        $index                       = isset($this->pending[$oid]) ? count($this->pending[$oid]) : 0;
        $this->pending[$oid][$index] = $entry;

        return $index;
    }
}
