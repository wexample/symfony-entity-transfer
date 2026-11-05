<?php

namespace Wexample\SymfonyEntityTransfer\Validator;

use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;
use Wexample\SymfonyEntityTransfer\Interface\ImportableEntityInterface;
use Wexample\SymfonyEntityTransfer\Helper\ReferenceHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * Validates that at least one relation field to a specific entity type is provided
 * in an import validation DTO.
 */
class ImportValidationDtoHasRelationValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate(
        $value,
        Constraint $constraint
    ): void {
        if (! $constraint instanceof ImportValidationDtoHasRelation) {
            throw new UnexpectedTypeException($constraint, ImportValidationDtoHasRelation::class);
        }

        if (null === $value) {
            return;
        }

        if (! $value instanceof AbstractEntityImportValidationDto) {
            throw new UnexpectedValueException($value, AbstractEntityImportValidationDto::class);
        }

        /** @var ImportableEntityInterface|AbstractEntity $entityType */
        $entityType = $constraint->entityType;
        $shortEntityName = ClassHelper::getShortName($entityType);

        // Check if at least one relation field is provided using the helper
        $hasRelation = ReferenceHelper::hasRelationField(
            validationDto: $value,
            targetEntityClass: $entityType,
            relationEntityFieldName: $constraint->fieldName
        );

        // Get available fields for the error message
        $existingPossibleFields = ReferenceHelper::getAvailableRelationFields(
            validationDto: $value,
            targetEntityClass: $entityType,
            relationEntityFieldName: $constraint->fieldName
        );

        // Only add a violation if the relation is required and no relation field is provided
        if (! $hasRelation && $constraint->required) {
            $fieldsList = implode(', ', $existingPossibleFields);
            $violationBuilder = $this->context->buildViolation($constraint->message)
                ->setParameter('{{ entitySource }}', ClassHelper::getShortName($value::getEntityClassName()))
                ->setParameter('{{ entityTarget }}', $shortEntityName)
                ->setParameter('{{ fields }}', $fieldsList);

            if (! empty($existingPossibleFields)) {
                $violationBuilder->atPath($existingPossibleFields[0]);
            }

            $violationBuilder->addViolation();
        }
    }
}
