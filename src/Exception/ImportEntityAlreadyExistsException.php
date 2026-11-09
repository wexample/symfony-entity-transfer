<?php

namespace Wexample\SymfonyEntityTransfer\Exception;

use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyApi\Exception\AbstractApiException;
use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;

/**
 * Exception thrown when an entity referenced by ID cannot be found during import
 */
class ImportEntityAlreadyExistsException extends AbstractApiException
{
    public function __construct(
        AbstractEntityImportValidationDto $validationDto,
    ) {
        parent::__construct(
            message: sprintf(
                'An ID was provided for an entity of type "%s" in the import dataset, which is not allowed.',
                ClassHelper::getFieldName($validationDto::getEntityClassName())
            ),
        );
    }

    public function getApiInternalCodeParts(): array
    {
        return [
            'ENTITY',
            'ALREADY_EXISTS',
        ];
    }
}
