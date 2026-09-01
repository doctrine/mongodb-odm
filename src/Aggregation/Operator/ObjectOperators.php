<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

/**
 * Interface containing all object aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface ObjectOperators
{
    /**
     * Returns the value of a specified field from a document. If you don't
     * specify an object, $getField returns the value of the field from
     * $$CURRENT.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/getField/
     */
    public function getField(mixed $field, mixed $input = null): static;

    /**
     * Combines multiple documents into a single document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/mergeObjects/
     */
    public function mergeObjects(mixed $expression, mixed ...$expressions): static;

    /**
     * Converts a document to an array. The return array contains an element for
     * each field/value pair in the original document. Each element in the
     * return array is a document that contains two fields k and v:
     *      The k field contains the field name in the original document.
     *      The v field contains the value of the field in the original document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/objectToArray/
     */
    public function objectToArray(mixed $object): static;

    /**
     * Adds, updates, or removes a specified field in a document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setField/
     */
    public function setField(mixed $field, mixed $input, mixed $value): static;
}
