<?php

namespace Wexample\SymfonyEntityTransfer\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that at least one relation field to a specific entity type is provided
 * in an import validation DTO.
 *
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class ImportValidationDtoHasRelation extends Constraint
{
    public string $message = 'At least one relation field from validation DTO of {{ entitySource }} to {{ entityTarget }} must be provided. Possible fields: {{ fields }}.';
    public string $entityType;

    /**
     * Custom base field name to use instead of the entity short name.
     * If not specified, the lowercase entity short name will be used.
     */
    public ?string $fieldName = null;

    /**
     * Whether this relation is required.
     * If false, the validation will pass even if no relation field is provided.
     */
    public bool $required = false;

    public function __construct(
        string $entityType,
        array $options = [],
        ?string $message = null,
        ?array $groups = null,
        ?string $fieldName = null,
        bool $required = false
    ) {
        $options['entityType'] = $entityType;

        if ($fieldName !== null) {
            $options['fieldName'] = $fieldName;
        }

        if ($required !== false) {
            $options['required'] = $required;
        }

        if ($message !== null) {
            $this->message = $message;
        } elseif (isset($options['message'])) {
            $this->message = $options['message'];
        }

        parent::__construct($options, $groups);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions(): array
    {
        return ['entityType'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return ImportValidationDtoHasRelationValidator::class;
    }
}
