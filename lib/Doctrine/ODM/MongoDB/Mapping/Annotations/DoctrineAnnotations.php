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

use Doctrine\Common\Annotations\Annotation;

abstract class AbstractDocument extends Annotation {}
/** @Annotation */
final class Document extends AbstractDocument
{
    public $db;
    public $collection;
    public $repositoryClass;
    public $indexes = array();
}
/** @Annotation */
final class EmbeddedDocument extends AbstractDocument
{
    public $indexes = array();
}
/** @Annotation */
final class MappedSuperclass extends AbstractDocument {}

/** @Annotation */
final class Inheritance extends Annotation
{
    public $type = 'NONE';
    public $discriminatorMap = array();
    public $discriminatorField;
}
/** @Annotation */
final class InheritanceType extends Annotation {}
/** @Annotation */
final class DiscriminatorField extends Annotation
{
    public $name;
    public $fieldName;
}
/** @Annotation */
final class DiscriminatorMap extends Annotation {}
/** @Annotation */
final class DiscriminatorValue extends Annotation {}

/** @Annotation */
final class Indexes extends Annotation {}
abstract class AbstractIndex extends Annotation
{
    public $keys = array();
    public $name;
    public $dropDups;
    public $background;
    public $safe;
    public $order;
    public $unique = false;
    public $options = array();
}
/** @Annotation */
final class Index extends AbstractIndex {}
/** @Annotation */
final class UniqueIndex extends AbstractIndex
{
    public $unique = true;
}

/** @Annotation */
final class Version extends Annotation {}
/** @Annotation */
final class Lock extends Annotation {}

abstract class AbstractField extends Annotation
{
    public $name;
    public $type = 'string';
    public $nullable = false;
    public $options = array();
    public $strategy;
}
/** @Annotation */
final class Field extends AbstractField {}
/** @Annotation */
final class Id extends AbstractField
{
    public $id = true;
    public $type = 'id';
    public $strategy = 'auto';
}
/** @Annotation */
final class Hash extends AbstractField
{
    public $type = 'hash';
}
/** @Annotation */
final class Boolean extends AbstractField
{
    public $type = 'boolean';
}
/** @Annotation */
final class Int extends AbstractField
{
    public $type = 'int';
}
/** @Annotation */
final class Float extends AbstractField
{
    public $type = 'float';
}
/** @Annotation */
final class String extends AbstractField
{
    public $type = 'string';
}
/** @Annotation */
final class Date extends AbstractField
{
    public $type = 'date';
}
/** @Annotation */
final class Key extends AbstractField
{
    public $type = 'key';
}
/** @Annotation */
final class Timestamp extends AbstractField
{
    public $type = 'timestamp';
}
/** @Annotation */
final class Bin extends AbstractField
{
    public $type = 'bin';
}
/** @Annotation */
final class BinFunc extends AbstractField
{
    public $type = 'bin_func';
}
/** @Annotation */
final class BinUUID extends AbstractField
{
    public $type = 'bin_uuid';
}
/** @Annotation */
final class BinMD5 extends AbstractField
{
    public $type = 'bin_md5';
}
/** @Annotation */
final class BinCustom extends AbstractField
{
    public $type = 'bin_custom';
}
/** @Annotation */
final class File extends AbstractField
{
    public $type = 'file';
    public $file = true;
}
/** @Annotation */
final class Increment extends AbstractField
{
    public $type = 'increment';
}
/** @Annotation */
final class ObjectId extends AbstractField
{
    public $type = 'object_id';
}
/** @Annotation */
final class Collection extends AbstractField
{
    public $type = 'collection';
    public $strategy = 'pushAll'; // pushAll, set
}
/** @Annotation */
final class EmbedOne extends AbstractField
{
    public $type = 'one';
    public $embedded = true;
    public $targetDocument;
    public $discriminatorField;
    public $discriminatorMap;
}
/** @Annotation */
final class EmbedMany extends AbstractField
{
    public $type = 'many';
    public $embedded = true;
    public $targetDocument;
    public $discriminatorField;
    public $discriminatorMap;
    public $strategy = 'pushAll'; // pushAll, set
}
/** @Annotation */
final class ReferenceOne extends AbstractField
{
    public $type = 'one';
    public $reference = true;
    public $simple = false;
    public $targetDocument;
    public $discriminatorField;
    public $discriminatorMap;
    public $cascade;
    public $inversedBy;
    public $mappedBy;
    public $repositoryMethod;
    public $sort = array();
    public $criteria = array();
    public $limit;
    public $skip;
}
/** @Annotation */
final class ReferenceMany extends AbstractField
{
    public $type = 'many';
    public $reference = true;
    public $simple = false;
    public $targetDocument;
    public $discriminatorField;
    public $discriminatorMap;
    public $cascade;
    public $inversedBy;
    public $mappedBy;
    public $repositoryMethod;
    public $sort = array();
    public $criteria = array();
    public $limit;
    public $skip;
    public $strategy = 'pushAll'; // pushAll, set
}
/** @Annotation */
final class NotSaved extends AbstractField
{
    public $notSaved = true;
}
/** @Annotation */
final class Distance extends AbstractField
{
    public $distance = true;
}
/** @Annotation */
final class AlsoLoad extends Annotation
{
    public $name;
}
/** @Annotation */
final class ChangeTrackingPolicy extends Annotation {}

/* Annotations for lifecycle callbacks */

/** @Annotation */
final class PrePersist extends Annotation {}
/** @Annotation */
final class PostPersist extends Annotation {}
/** @Annotation */
final class PreUpdate extends Annotation {}
/** @Annotation */
final class PostUpdate extends Annotation {}
/** @Annotation */
final class PreRemove extends Annotation {}
/** @Annotation */
final class PostRemove extends Annotation {}
/** @Annotation */
final class PreLoad extends Annotation {}
/** @Annotation */
final class PostLoad extends Annotation {}