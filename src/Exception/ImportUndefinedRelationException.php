<?php

namespace Wexample\SymfonyEntityTransfer\Exception;

use Exception;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;

/**
 * Exception thrown when a relation referenced during import cannot be found
 */
class ImportUndefinedRelationException extends AbstractImportException
{
    /**
     * @param string $entityType The type of entity being imported
     * @param AbstractEntityImportValidationDto $validationDto The validation DTO
     * @param string|null $relationEntityFieldName Optional custom relation field name
     * @param int $code The exception code
     * @param Exception|null $previous The previous exception
     */
    public function __construct(
        string $entityType,
        AbstractEntityImportValidationDto $validationDto,
        ?string $relationEntityFieldName = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct(
            message: sprintf(
                'Missing relation to %s: No valid reference field found when importing %s.',
                $entityType,
                ClassHelper::getFieldName($validationDto::getEntityClassName())
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
            'RELATION_UNDEFINED',
        ];
    }
}
