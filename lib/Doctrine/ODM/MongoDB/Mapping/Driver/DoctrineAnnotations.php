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

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\Common\Annotations\Annotation;

final class Document extends Annotation
{
    public $db;
    public $collection;
    public $repositoryClass;
    public $indexes = array();
}
final class EmbeddedDocument extends Annotation {}
final class MappedSuperclass extends Annotation {}

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

final class Index extends Annotation
{
    public $keys = array();
    public $options = array();
}

final class Id extends Annotation
{
    public $id = true;
}
final class Field extends Annotation
{
    public $type = 'string';
}
final class Hash extends Annotation
{
    public $type = 'hash';
}
final class Boolean extends Annotation
{
    public $type = 'boolean';
}
final class Int extends Annotation
{
    public $type = 'int';
}
final class Float extends Annotation
{
    public $type = 'float';
}
final class String extends Annotation
{
    public $type = 'string';
}
final class Date extends Annotation
{
    public $type = 'date';
}
final class Key extends Annotation
{
    public $type = 'key';
}
final class Timestamp extends Annotation
{
    public $type = 'timestamp';
}
final class Bin extends Annotation
{
    public $type = 'bin';
}
final class BinFunc extends Annotation
{
    public $type = 'bin_func';
}
final class BinUUID extends Annotation
{
    public $type = 'bin_uuid';
}
final class BinMD5 extends Annotation
{
    public $type = 'bin_md5';
}
final class BinCustom extends Annotation
{
    public $type = 'bin_custom';
}
final class File extends Annotation
{
    public $type = 'file';
    public $file = true;
}

final class EmbedOne extends Annotation
{
    public $type = 'one';
    public $embedded = true;
    public $targetDocument;
}
final class EmbedMany extends Annotation
{
    public $type = 'many';
    public $embedded = true;
    public $targetDocument;
}
final class ReferenceOne extends Annotation
{
    public $type = 'one';
    public $reference = true;
    public $targetDocument;
    public $cascade;
}
final class ReferenceMany extends Annotation
{
    public $type = 'many';
    public $reference = true;
    public $targetDocument;
    public $cascade;
}

/* Annotations for lifecycle callbacks */
final class HasLifecycleCallbacks extends Annotation {}
final class PrePersist extends Annotation {}
final class PostPersist extends Annotation {}
final class PreUpdate extends Annotation {}
final class PostUpdate extends Annotation {}
final class PreRemove extends Annotation {}
final class PostRemove extends Annotation {}
final class PostLoad extends Annotation {}