<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

interface Annotation
{
}

abstract class AbstractDocument implements Annotation
{
}

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Document extends AbstractDocument
{
    /** @var string */
    public $db;

    /** @var string */
    public $collection;

    /** @var string */
    public $repositoryClass;

    /** @var array<Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractIndex> */
    public $indexes = array();
    public $requireIndexes = false;
    public $slaveOkay;
}

/**
 * @Annotation
 * @Target("CLASS")
 */
final class EmbeddedDocument extends AbstractDocument
{
    /** @var array<Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractIndex> */
    public $indexes = array();
}

/**
 * @Annotation
 * @Target("CLASS")
 */
final class MappedSuperclass extends AbstractDocument
{
}

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Inheritance implements Annotation
{
    /** @var string */
    public $type = 'NONE';

    /** @var array<string> */
    public $discriminatorMap = array();

    /** @var string */
    public $discriminatorField;
}

/**
 * @Annotation
 * @Target("CLASS")
 */
final class InheritanceType implements Annotation
{
    /** @var string */
    public $value;
}

/**
 * @Annotation
 * @Target("CLASS")
 */
final class DiscriminatorField implements Annotation
{
    /** @var string */
    public $name;

    /** @var string */
    public $fieldName;
}

/**
 * @Annotation
 * @Target("CLASS")
 */
final class DiscriminatorMap implements Annotation
{
    /** @var array<string> */
    public $value;
}

/**
 * @Annotation
 * @Target("CLASS")
 */
final class DiscriminatorValue implements Annotation
{
    /** @var string */
    public $value;
}

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Indexes implements Annotation
{
    /** @var array<Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractIndex> */
    public $value;
}

abstract class AbstractIndex implements Annotation
{
    /** @var array */
    public $keys = array();

    /** @var string */
    public $name;
    
    /** @var boolean */
    public $dropDups;

    /** @var boolean */
    public $background;

    /** @var boolean */
    public $safe;

    /** @var string */
    public $order;

    /** @var boolean */
    public $unique = false;

    /** @var array */
    public $options = array();
}

/**
 * @Annotation
 * @Target({"CLASS","ANNOTATION","PROPERTY"})
 */
final class Index extends AbstractIndex
{
}

/**
 * @Annotation
 * @Target({"CLASS","ANNOTATION","PROPERTY"})
 */
final class UniqueIndex extends AbstractIndex
{
    public $unique = true;
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Version implements Annotation
{
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Lock implements Annotation
{
}

abstract class AbstractField implements Annotation
{
    /** @var string */
    public $name;

    /** @var string */
    public $type = 'string';

    /** @var boolean */
    public $nullable = false;

    /** @var array */
    public $options = array();

    /** @var string */
    public $strategy;
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Field extends AbstractField
{
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Id extends AbstractField
{
    /** @var string */
    public $id = true;

    /** @var string */
    public $type = 'id';

    /** @var string */
    public $strategy = 'auto';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Hash extends AbstractField
{
    /** @var string */
    public $type = 'hash';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Boolean extends AbstractField
{
    /** @var string */
    public $type = 'boolean';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Int extends AbstractField
{
    /** @var string */
    public $type = 'int';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Float extends AbstractField
{
    /** @var string */
    public $type = 'float';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class String extends AbstractField
{
    /** @var string */
    public $type = 'string';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Date extends AbstractField
{
    /** @var string */
    public $type = 'date';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Key extends AbstractField
{
    /** @var string */
    public $type = 'key';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Timestamp extends AbstractField
{
    /** @var string */
    public $type = 'timestamp';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Bin extends AbstractField
{
    /** @var string */
    public $type = 'bin';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class BinFunc extends AbstractField
{
    /** @var string */
    public $type = 'bin_func';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class BinUUID extends AbstractField
{
    /** @var string */
    public $type = 'bin_uuid';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class BinMD5 extends AbstractField
{
    /** @var string */
    public $type = 'bin_md5';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class BinCustom extends AbstractField
{
    /** @var string */
    public $type = 'bin_custom';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class File extends AbstractField
{
    /** @var string */
    public $type = 'file';

    /** @var boolean */
    public $file = true;
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Increment extends AbstractField
{
    /** @var string */
    public $type = 'increment';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class ObjectId extends AbstractField
{
    /** @var string */
    public $type = 'object_id';
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Collection extends AbstractField
{
    /** @var string */
    public $type = 'collection';

    /** @var string */
    public $strategy = 'pushAll'; // pushAll, set
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class EmbedOne extends AbstractField
{
    /** @var string */
    public $type = 'one';

    /** @var boolean */
    public $embedded = true;

    /** @var string */
    public $targetDocument;

    /** @var string */
    public $discriminatorField;

    /** @var array<string> */
    public $discriminatorMap;
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class EmbedMany extends AbstractField
{
    /** @var string */
    public $type = 'many';

    /** @var boolean */
    public $embedded = true;

    /** @var string */
    public $targetDocument;

    /** @var string */
    public $discriminatorField;

    /** @var array<string> */
    public $discriminatorMap;

    /** @var string */
    public $strategy = 'pushAll'; // pushAll, set
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class ReferenceOne extends AbstractField
{
    /** @var string */
    public $type = 'one';

    /** @var boolean */
    public $reference = true;

    /** @var boolean */
    public $simple = false;

    /** @var string */
    public $targetDocument;

    /** @var string */
    public $discriminatorField;

    /** @var array<string> */
    public $discriminatorMap;

    /** @var array<string> */
    public $cascade;

    /** @var string */
    public $inversedBy;

    /** @var string */
    public $mappedBy;

    /** @var string */
    public $repositoryMethod;

    /** @var array<string> */
    public $sort = array();

    /** @var array<boolean> */
    public $criteria = array();

    /** @var integer */
    public $limit;

    /** @var boolean */
    public $skip;
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class ReferenceMany extends AbstractField
{

    /** @var string */
    public $type = 'many';

    /** @var boolean */
    public $reference = true;

    /** @var boolean */
    public $simple = false;

    /** @var string */
    public $targetDocument;

    /** @var string */
    public $discriminatorField;

    /** @var array<string> */
    public $discriminatorMap;

    /** @var array<string> */
    public $cascade;

    /** @var string */
    public $inversedBy;

    /** @var string */
    public $mappedBy;

    /** @var string */
    public $repositoryMethod;

    /** @var array<string> */
    public $sort = array();

    /** @var array<boolean> */
    public $criteria = array();

    /** @var integer */
    public $limit;

    /** @var boolean */
    public $skip;

    /** @var string */
    public $strategy = 'pushAll'; // pushAll, set
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class NotSaved extends AbstractField
{
    /** @var boolean */
    public $notSaved = true;
}

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Distance extends AbstractField
{
    /** @var boolean */
    public $distance = true;
}

/**
 * @Annotation
 * @Target({"PROPERTY","METHOD"})
 */
final class AlsoLoad implements Annotation
{
    /** @var array<string> */
    public $value;

    /** @var string */
    public $name;
}

/**
 * @Annotation
 * @Target("CLASS")
 */
final class ChangeTrackingPolicy implements Annotation
{
    /** @var string */
    public $value;
}

/* Annotations for lifecycle callbacks */

/**
 * @Annotation
 * @Target("METHOD")
 */
final class PrePersist implements Annotation
{
}

/**
 * @Annotation
 * @Target("METHOD")
 */
final class PostPersist implements Annotation
{
}

/**
 * @Annotation
 * @Target("METHOD")
 */
final class PreUpdate implements Annotation
{
}

/**
 * @Annotation
 * @Target("METHOD")
 */
final class PostUpdate implements Annotation
{
}

/**
 * @Annotation
 * @Target("METHOD")
 */
final class PreRemove implements Annotation
{
}

/**
 * @Annotation
 * @Target("METHOD")
 */
final class PostRemove implements Annotation
{
}

/**
 * @Annotation
 * @Target("METHOD")
 */
final class PreLoad implements Annotation
{
}

/**
 * @Annotation
 * @Target("METHOD")
 */
final class PostLoad implements Annotation
{
}
