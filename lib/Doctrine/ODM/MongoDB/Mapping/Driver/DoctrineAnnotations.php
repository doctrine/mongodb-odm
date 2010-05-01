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

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\Common\Annotations\Annotation;

final class Document extends Annotation
{
    public $db;
    public $collection;
    public $indexes = array();
}

final class Index extends Annotation
{
    public $keys = array();
    public $options = array();
}

final class Field extends Annotation
{
    public $name;
}

final class File extends Annotation
{
    public $file = true;
}

final class Id extends Annotation
{
    public $id = true;
}

final class EmbedOne extends Annotation
{
    public $type = 'one';
    public $embedded = true;
    public $name;
    public $targetDocument;
}

final class EmbedMany extends Annotation
{
    public $type = 'many';
    public $embedded = true;
    public $name;
    public $targetDocument;
}

final class ReferenceOne extends Annotation
{
    public $type = 'one';
    public $reference = true;
    public $name;
    public $targetDocument;
    public $cascadeDelete = false;
}

final class ReferenceMany extends Annotation
{
    public $type = 'many';
    public $reference = true;
    public $name;
    public $targetDocument;
    public $cascadeDelete = false;
}