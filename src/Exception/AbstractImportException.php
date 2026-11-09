<?php

namespace Wexample\SymfonyEntityTransfer\Exception;

use Wexample\SymfonyApi\Exception\AbstractApiException;
use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;
use Wexample\SymfonyEntityTransfer\Dto\AbstractImportValidationDto;
use Wexample\SymfonyEntityTransfer\Helper\ReferenceHelper;

/**
 * Base exception class for all import-related exceptions
 */
abstract class AbstractImportException extends AbstractApiException
{
    public function __construct(
        string $message,
        AbstractEntityImportValidationDto $validationDto,
        string $entityType,
        ?string $relationEntityFieldName = null,
        int $code = 0,
        ?string $internalCodeSuffix = null,
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            message: $message . ' ' . $this->getAvailableFieldsMessage(
                $validationDto,
                $entityType,
                $relationEntityFieldName,
            ),
            code: $code,
            internalCodeSuffix: $internalCodeSuffix,
            context: $context,
            previous: $previous
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getApiInternalCodeParts(): array
    {
        return array_merge([
            'IMPORT',
        ], $this->getImportInternalCodeParts());
    }

    /**
     * Get the import-specific internal code parts
     *
     * @return array
     */
    abstract public function getImportInternalCodeParts(): array;

    /**
     * Get formatted message about available fields
     *
     * @param AbstractImportValidationDto $validationDto
     * @param string $entityType
     * @param string|null $relationEntityFieldName
     * @return string
     */
    protected function getAvailableFieldsMessage(
        AbstractImportValidationDto $validationDto,
        string $entityType,
        ?string $relationEntityFieldName = null
    ): string {
        $availableFields = ReferenceHelper::getAvailableRelationFields(
            $validationDto,
            $entityType,
            $relationEntityFieldName
        );

        if (empty($availableFields)) {
            return 'No relation fields available on this entity.';
        }

        return sprintf(
            'Available relation fields: %s',
            implode(', ', $availableFields)
        );
    }
}
