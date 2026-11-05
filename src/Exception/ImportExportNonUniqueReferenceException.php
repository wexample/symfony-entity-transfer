<?php

namespace Wexample\SymfonyEntityTransfer\Exception;

use Wexample\SymfonyApi\Exception\AbstractApiException;

/**
 * Exception thrown when an entity referenced by its unique field cannot be found during import
 */
class ImportExportNonUniqueReferenceException extends AbstractApiException
{
    public function __construct(
        string $nonUniqueReference,
        string $entityClass,
        int $code = 0,
        ?string $internalCodeSuffix = null,
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            sprintf(
                'A non unique reference name was found: "%s" for entity of type "%s"',
                $nonUniqueReference,
                $entityClass
            ),
            $code,
            $internalCodeSuffix,
            $context,
            $previous
        );
    }

    public function getApiInternalCodeParts(): array
    {
        return [
            'NON_UNIQUE_REFERENCE',
        ];
    }
}
