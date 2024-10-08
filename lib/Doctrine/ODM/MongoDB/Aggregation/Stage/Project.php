<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Fluent interface for adding a $project stage to an aggregation pipeline.
 *
 * @phpstan-import-type OperatorExpression from Expr
 * @phpstan-type ProjectStageExpression array{'$project': array<string, OperatorExpression|mixed>}
 */
class Project extends Operator
{
    /** @phpstan-return ProjectStageExpression */
    public function getExpression(): array
    {
        return [
            '$project' => $this->expr->getExpression(),
        ];
    }

    /**
     * Shorthand method to define which fields to be included.
     *
     * @param string[] $fields
     */
    public function includeFields(array $fields): static
    {
        foreach ($fields as $fieldName) {
            $this->field($fieldName)->expression(true);
        }

        return $this;
    }

    /**
     * Shorthand method to define which fields to be excluded.
     *
     * If you specify the exclusion of a field other than _id, you cannot employ
     * any other $project specification forms.
     *
     * @param string[] $fields
     */
    public function excludeFields(array $fields): static
    {
        foreach ($fields as $fieldName) {
            $this->field($fieldName)->expression(false);
        }

        return $this;
    }
}
