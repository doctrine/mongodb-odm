<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

enum BsonType: string
{
    case Double            = 'double';
    case String            = 'string';
    case Object            = 'object';
    case Array             = 'array';
    case BinaryData        = 'binData';
    case ObjectId          = 'objectId';
    case Boolean           = 'bool';
    case Date              = 'date';
    case Null              = 'null';
    case RegularExpression = 'regex';
    case JavaScript        = 'javascript';
    case Int32             = 'int';
    case Timestamp         = 'timestamp';
    case Int64             = 'long';
    case Decimal128        = 'decimal';
    case MinKey            = 'minKey';
    case MaxKey            = 'maxKey';
}
