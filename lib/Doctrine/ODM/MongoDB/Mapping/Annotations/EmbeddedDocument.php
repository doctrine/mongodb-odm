<?php

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Identifies a class as a document that can be embedded but not stored by itself
 *
 * @Annotation
 */
final class EmbeddedDocument extends AbstractDocument
{
    public $indexes = array();
}
