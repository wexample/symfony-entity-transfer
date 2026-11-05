<?php

namespace Wexample\SymfonyEntityTransfer\Exception;

use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;
use Wexample\SymfonyEntityTransfer\Class\RelationFieldInfo;
use Exception;

/**
 * Exception thrown when an entity referenced by its unique field cannot be found during import
 */
class ImportUniqueFieldNotFoundException extends AbstractImportException
{
    /**
     * @param string $entityType The type of entity being imported
     * @param string $searchField The unique field name used for searching
     * @param AbstractEntityImportValidationDto $validationDto The validation DTO
     * @param RelationFieldInfo $relationInfo Information about the relation field
     * @param string|null $relationEntityFieldName Optional custom relation field name
     * @param int $code The exception code
     * @param Exception|null $previous The previous exception
     */
    public function __construct(
        string $entityType,
        string $searchField,
        AbstractEntityImportValidationDto $validationDto,
        RelationFieldInfo $relationInfo,
        ?string $relationEntityFieldName = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct(
            message: sprintf(
                'Cannot find %s with %s="%s". No entity with this unique field value exists in the database.',
                $entityType,
                $searchField,
                $relationInfo->value,
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
            'UNIQUE_FIELD',
            'NOT_FOUND',
        ];
    }
}
