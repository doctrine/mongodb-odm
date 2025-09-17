<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use Exception;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Uuid;

use function get_debug_type;
use function sprintf;

class BinaryUuidType extends Type
{
    public function convertToDatabaseValue(mixed $value): ?Binary
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Binary) {
            return $value;
        }

        if (! $value instanceof Uuid) {
            $value = Uuid::fromString($value);
        }

        return new Binary($value->toBinary(), Binary::TYPE_UUID);
    }

    public function convertToPHPValue(mixed $value): Uuid
    {
        if ($value instanceof Uuid) {
            return $value;
        }

        if (! $value instanceof Binary) {
            throw new Exception(sprintf('Invalid data of type "%s" received for Uuid', get_debug_type($value)));
        }

        if ($value->getType() !== Binary::TYPE_UUID) {
            throw new Exception(sprintf('Invalid binary data of type %d received for Uuid', $value->getType()));
        }

        return Uuid::fromBinary($value->getData());
    }

    public function closureToMongo(): string
    {
        return <<<'PHP'
$return = match (true) {
    $value === null => null,
    $value instanceof \MongoDB\BSON\Binary => $value,
    $value instanceof \Symfony\Component\Uid\Uuid => new \MongoDB\BSON\Binary($value->toBinary(), \MongoDB\BSON\Binary::TYPE_UUID),
    is_string($value) => new \MongoDB\BSON\Binary(\Symfony\Component\Uid\Uuid::fromString($value)->toBinary(), \MongoDB\BSON\Binary::TYPE_UUID),
    default => throw new InvalidArgumentException(sprintf('Invalid data type %s received for UUID', get_debug_type($value))),
};
PHP;
    }

    public function closureToPHP(): string
    {
        return '$return = $value instanceof \Symfony\Component\Uid\Uuid ? $value : \Symfony\Component\Uid\Uuid::fromBinary($value->getData());';
    }
}
