<?php

namespace Wexample\SymfonyEntityTransfer\Exception;

use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;
use Wexample\SymfonyEntityTransfer\Class\RelationFieldInfo;
use Exception;

/**
 * Exception thrown when a relation field contains a value of invalid type
 */
class ImportInvalidRelationTypeException extends AbstractImportException
{
    /**
     * @param string $entityType The type of entity being imported
     * @param AbstractEntityImportValidationDto $validationDto The validation DTO
     * @param RelationFieldInfo $relationInfo Information about the relation field
     * @param string $expectedType The expected type of the relation
     * @param string|null $relationEntityFieldName Optional custom relation field name
     * @param int $code The exception code
     * @param Exception|null $previous The previous exception
     */
    public function __construct(
        string $entityType,
        AbstractEntityImportValidationDto $validationDto,
        RelationFieldInfo $relationInfo,
        string $expectedType,
        ?string $relationEntityFieldName = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct(
            message: sprintf(
                'Invalid type for relation field "%s" in %s: Expected a %s object, got "%s" instead',
                $relationInfo->fieldName,
                $entityType,
                $expectedType,
                get_debug_type($relationInfo->value),
            ),
            validationDto: $validationDto,
            entityType: $entityType,
            relationEntityFieldName: $relationEntityFieldName,
            code: $code,
            previous: $previous
        );
    }

    public function getImportInternalCodeParts(): array
    {
        return [
            'RELATION',
            'INVALID_TYPE',
        ];
    }
}
