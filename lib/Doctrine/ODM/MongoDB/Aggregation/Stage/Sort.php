<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $sort stage to an aggregation pipeline.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.2
 */
class Sort extends Stage
{
    /**
     * @var array
     */
    private $sort = [];

    /**
     * @param Builder $builder
     * @param array|string $fieldName Field name or array of field/order pairs
     * @param int|string $order       Field order (if one field is specified)
     */
    public function __construct(Builder $builder, $fieldName, $order = null)
    {
        parent::__construct($builder);

        $allowedMetaSort = ['textScore'];

        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order];

        foreach ($fields as $fieldName => $order) {
            if (is_string($order)) {
                if (in_array($order, $allowedMetaSort)) {
                    $order = ['$meta' => $order];
                } else {
                    $order = strtolower($order) === 'asc' ? 1 : -1;
                }
            }

            $this->sort[$fieldName] = $order;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$sort' => $this->sort
        ];
    }
}
