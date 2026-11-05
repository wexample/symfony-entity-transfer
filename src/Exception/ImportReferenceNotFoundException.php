<?php

namespace Wexample\SymfonyEntityTransfer\Exception;

use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;
use Wexample\SymfonyEntityTransfer\Class\RelationFieldInfo;
use Wexample\SymfonyEntityTransfer\Service\ImportReferencesBagService;
use Exception;

/**
 * Exception thrown when a reference (_ref) cannot be found during import
 */
class ImportReferenceNotFoundException extends AbstractImportException
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
        ImportReferencesBagService $referencesBag,
        ?string $relationEntityFieldName = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct(
            message: sprintf(
                'Reference "%s" (field: %s) for %s was not found. This reference does not exist in the current import batch. Existing references are: %s',
                $relationInfo->value,
                $relationInfo->fieldName,
                $entityType,
                implode(', ', $referencesBag->listImportReferences())
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
            'REFERENCE',
            'NOT_FOUND',
        ];
    }
}
