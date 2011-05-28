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
final class Document extends AbstractDocument
{
    public $db;
    public $collection;
    public $repositoryClass;
    public $indexes = array();
}
final class EmbeddedDocument extends AbstractDocument
{
    public $indexes = array();
}
final class MappedSuperclass extends AbstractDocument {}

final class Inheritance extends Annotation
{
    public $type = 'NONE';
    public $discriminatorMap = array();
    public $discriminatorField;
}
final class InheritanceType extends Annotation {}
final class DiscriminatorField extends Annotation
{
    public $name;
    public $fieldName;
}
final class DiscriminatorMap extends Annotation {}
final class DiscriminatorValue extends Annotation {}

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
final class Index extends AbstractIndex {}
final class UniqueIndex extends AbstractIndex
{
    public $unique = true;
}

final class Version extends Annotation {}
final class Lock extends Annotation {}

abstract class AbstractField extends Annotation
{
    public $name;
    public $type = 'string';
    public $nullable = false;
    public $options = array();
    public $strategy;
}
final class Field extends AbstractField {}
final class Id extends AbstractField
{
    public $id = true;
    public $type = 'id';
    public $strategy = 'auto';
}
final class Hash extends AbstractField
{
    public $type = 'hash';
}
final class Boolean extends AbstractField
{
    public $type = 'boolean';
}
final class Int extends AbstractField
{
    public $type = 'int';
}
final class Float extends AbstractField
{
    public $type = 'float';
}
final class String extends AbstractField
{
    public $type = 'string';
}
final class Date extends AbstractField
{
    public $type = 'date';
}
final class Key extends AbstractField
{
    public $type = 'key';
}
final class Timestamp extends AbstractField
{
    public $type = 'timestamp';
}
final class Bin extends AbstractField
{
    public $type = 'bin';
}
final class BinFunc extends AbstractField
{
    public $type = 'bin_func';
}
final class BinUUID extends AbstractField
{
    public $type = 'bin_uuid';
}
final class BinMD5 extends AbstractField
{
    public $type = 'bin_md5';
}
final class BinCustom extends AbstractField
{
    public $type = 'bin_custom';
}
final class File extends AbstractField
{
    public $type = 'file';
    public $file = true;
}
final class Increment extends AbstractField
{
    public $type = 'increment';
}
final class Collection extends AbstractField
{
    public $type = 'collection';
    public $strategy = 'pushAll'; // pushAll, set
}
final class EmbedOne extends AbstractField
{
    public $type = 'one';
    public $embedded = true;
    public $targetDocument;
    public $discriminatorField;
    public $discriminatorMap;
}
final class EmbedMany extends AbstractField
{
    public $type = 'many';
    public $embedded = true;
    public $targetDocument;
    public $discriminatorField;
    public $discriminatorMap;
    public $strategy = 'pushAll'; // pushAll, set
}
final class ReferenceOne extends AbstractField
{
    public $type = 'one';
    public $reference = true;
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
final class ReferenceMany extends AbstractField
{
    public $type = 'many';
    public $reference = true;
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
final class NotSaved extends AbstractField
{
    public $notSaved = true;
}
final class Distance extends AbstractField
{
    public $distance = true;
}
final class AlsoLoad extends Annotation
{
    public $name;
}
final class ChangeTrackingPolicy extends Annotation {}

/* Annotations for lifecycle callbacks */
final class PrePersist extends Annotation {}
final class PostPersist extends Annotation {}
final class PreUpdate extends Annotation {}
final class PostUpdate extends Annotation {}
final class PreRemove extends Annotation {}
final class PostRemove extends Annotation {}
final class PreLoad extends Annotation {}
final class PostLoad extends Annotation {}