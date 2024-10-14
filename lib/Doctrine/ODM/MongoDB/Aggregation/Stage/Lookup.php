<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\Persistence\Mapping\MappingException as BaseMappingException;
use InvalidArgumentException;

/**
 * Fluent interface for building aggregation pipelines.
 *
 * @phpstan-import-type PipelineExpression from Builder
 * @phpstan-type PipelineParamType Builder|Stage|PipelineExpression
 * @phpstan-type LookupStageExpression array{
 *     '$lookup': array{
 *         from: string,
 *         'as'?: string,
 *         localField?: string,
 *         foreignField?: string,
 *         pipeline?: PipelineExpression,
 *         let?: array<string, mixed>,
 *     }
 * }
 */
class Lookup extends Stage
{
    private ?ClassMetadata $targetClass = null;

    private string $from;

    private ?string $localField = null;

    private ?string $foreignField = null;

    private ?string $as = null;

    /** @var array<string, mixed>|null */
    private ?array $let = null;

    /**
     * @var Builder|array<array<string, mixed>>|null
     * @phpstan-var Builder|PipelineExpression|null
     */
    private Builder|array|null $pipeline = null;

    private bool $excludeLocalAndForeignField = false;

    public function __construct(Builder $builder, string $from, private DocumentManager $dm, private ClassMetadata $class)
    {
        parent::__construct($builder);

        $this->from($from);
    }

    /**
     * Specifies the name of the new array field to add to the input documents.
     *
     * The new array field contains the matching documents from the from
     * collection. If the specified name already exists in the input document,
     * the existing field is overwritten.
     */
    public function alias(string $alias): static
    {
        $this->as = $alias;

        return $this;
    }

    /**
     * Specifies the collection or field name in the same database to perform the join with.
     */
    public function from(string $from): static
    {
        // $from can either be
        // a) a field name indicating a reference to a different document. Currently, only REFERENCE_STORE_AS_ID is supported
        // b) a Class name
        // c) a collection name
        // In cases b) and c) the local and foreign fields need to be filled
        if ($this->class->hasReference($from)) {
            return $this->fromReference($from);
        }

        // Check if mapped class with given name exists
        try {
            $this->targetClass = $this->dm->getClassMetadata($from);
        } catch (BaseMappingException) {
            $this->from = $from;

            return $this;
        }

        $this->from = $this->targetClass->getCollection();

        return $this;
    }

    /** @phpstan-return LookupStageExpression */
    public function getExpression(): array
    {
        $lookup = [
            'from' => $this->from,
        ];

        if ($this->as !== null) {
            $lookup['as'] = $this->as;
        }

        if (! $this->excludeLocalAndForeignField) {
            if ($this->localField !== null) {
                $lookup['localField'] = $this->localField;
            }

            if ($this->foreignField !== null) {
                $lookup['foreignField'] = $this->foreignField;
            }
        }

        if (! empty($this->let)) {
            $lookup['let'] = $this->let;
        }

        if ($this->pipeline !== null) {
            if ($this->pipeline instanceof Builder) {
                $lookup['pipeline'] = $this->pipeline->getPipeline(false);
            } else {
                $lookup['pipeline'] = $this->pipeline;
            }
        }

        return ['$lookup' => $lookup];
    }

    /**
     * Specifies the field from the documents input to the $lookup stage.
     *
     * $lookup performs an equality match on the localField to the foreignField
     * from the documents of the from collection. If an input document does not
     * contain the localField, the $lookup treats the field as having a value of
     * null for matching purposes.
     */
    public function localField(string $localField): static
    {
        $this->localField = $this->prepareFieldName($localField, $this->class);

        return $this;
    }

    /**
     * Specifies the field from the documents in the from collection.
     *
     * $lookup performs an equality match on the foreignField to the localField
     * from the input documents. If a document in the from collection does not
     * contain the foreignField, the $lookup treats the value as null for
     * matching purposes.
     */
    public function foreignField(string $foreignField): static
    {
        $this->foreignField = $this->prepareFieldName($foreignField, $this->targetClass);

        return $this;
    }

    /**
     * Optional. Specifies variables to use in the pipeline stages.
     *
     * Use the variable expressions to access the fields from
     * the joined collection's documents that are input to the pipeline.
     *
     * @param array<string, mixed> $let
     */
    public function let(array $let): static
    {
        $this->let = $let;

        return $this;
    }

    /**
     * Specifies the pipeline to run on the joined collection.
     *
     * The pipeline determines the resulting documents from the joined collection.
     * To return all documents, specify an empty pipeline [].
     *
     * The pipeline cannot directly access the joined document fields.
     * Instead, define variables for the joined document fields using the let option
     * and then reference the variables in the pipeline stages.
     *
     * @param Builder|Stage|array<array<string, mixed>> $pipeline
     * @phpstan-param PipelineParamType $pipeline
     */
    public function pipeline($pipeline): static
    {
        if ($pipeline instanceof Stage) {
            $this->pipeline = $pipeline->builder;
        } else {
            $this->pipeline = $pipeline;
        }

        if ($this->builder === $this->pipeline) {
            throw new InvalidArgumentException('Cannot use the same Builder instance for $lookup pipeline.');
        }

        return $this;
    }

    /**
     * Excludes localField and foreignField from an expression.
     */
    public function excludeLocalAndForeignField(): static
    {
        $this->excludeLocalAndForeignField = true;

        return $this;
    }

    protected function prepareFieldName(string $fieldName, ?ClassMetadata $class = null): string
    {
        if (! $class) {
            return $fieldName;
        }

        return $this->getDocumentPersister($class)->prepareFieldName($fieldName);
    }

    /** @throws MappingException */
    private function fromReference(string $fieldName): static
    {
        if (! $this->class->hasReference($fieldName)) {
            MappingException::referenceMappingNotFound($this->class->name, $fieldName);
        }

        $referenceMapping  = $this->class->getFieldMapping($fieldName);
        $this->targetClass = $this->dm->getClassMetadata($referenceMapping['targetDocument']);

        $this->from = $this->targetClass->getCollection();

        if ($referenceMapping['isOwningSide']) {
            switch ($referenceMapping['storeAs']) {
                case ClassMetadata::REFERENCE_STORE_AS_ID:
                case ClassMetadata::REFERENCE_STORE_AS_REF:
                    $referencedFieldName = ClassMetadata::getReferenceFieldName($referenceMapping['storeAs'], $referenceMapping['name']);
                    break;

                default:
                    throw MappingException::cannotLookupDbRefReference($this->class->name, $fieldName);
            }

            $this
                ->foreignField('_id')
                ->localField($referencedFieldName);
        } else {
            if (isset($referenceMapping['repositoryMethod']) || ! isset($referenceMapping['mappedBy'])) {
                throw MappingException::repositoryMethodLookupNotAllowed($this->class->name, $fieldName);
            }

            $mappedByMapping = $this->targetClass->getFieldMapping($referenceMapping['mappedBy']);
            switch ($mappedByMapping['storeAs']) {
                case ClassMetadata::REFERENCE_STORE_AS_ID:
                case ClassMetadata::REFERENCE_STORE_AS_REF:
                    $referencedFieldName = ClassMetadata::getReferenceFieldName($mappedByMapping['storeAs'], $mappedByMapping['name']);
                    break;

                default:
                    throw MappingException::cannotLookupDbRefReference($this->class->name, $fieldName);
            }

            $this
                ->localField('_id')
                ->foreignField($referencedFieldName)
                ->alias($fieldName);
        }

        return $this;
    }

    private function getDocumentPersister(ClassMetadata $class): DocumentPersister
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($class->name);
    }
}
