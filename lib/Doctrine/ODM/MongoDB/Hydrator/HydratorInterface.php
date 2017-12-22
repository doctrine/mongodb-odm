<?php

namespace Doctrine\ODM\MongoDB\Hydrator;

/**
 * The HydratorInterface defines methods all hydrator need to implement
 *
 * @since       1.0
 */
interface HydratorInterface
{
    /**
     * Hydrate array of MongoDB document data into the given document object.
     *
     * @param object $document  The document object to hydrate the data into.
     * @param array $data The array of document data.
     * @param array $hints Any hints to account for during reconstitution/lookup of the document.
     * @return array $values The array of hydrated values.
     */
    public function hydrate($document, $data, array $hints = array());
}
