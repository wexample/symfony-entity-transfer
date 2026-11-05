<?php

namespace Wexample\SymfonyEntityTransfer\Exception;

use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;
use Wexample\SymfonyEntityTransfer\Class\RelationFieldInfo;
use Exception;

/**
 * Exception thrown when an entity referenced by ID cannot be found during import
 */
class ImportEntityNotFoundException extends AbstractImportException
{
    /**
     * @param string $entityType The type of entity being imported
     * @param AbstractEntityImportValidationDto $validationDto The validation DTO
     * @param RelationFieldInfo $relationInfo Information about the relation field
     * @param string|null $relationEntityFieldName Optional custom relation field name
     * @param int $code The exception code
     * @param Exception|null $previous The previous exception
     */
    public function __construct(
        string $entityType,
        AbstractEntityImportValidationDto $validationDto,
        RelationFieldInfo $relationInfo,
        ?string $relationEntityFieldName = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct(
            message: sprintf(
                'Cannot find %s with ID "%s" (referenced by field "%s"). The entity with this ID does not exist in the database.',
                $entityType,
                $relationInfo->value,
                $relationInfo->fieldName
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
            'ENTITY',
            'NOT_FOUND',
        ];
    }
}
