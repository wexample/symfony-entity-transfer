<?php

namespace Wexample\SymfonyEntityTransfer\Interface;

use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportDto;
use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;
use Wexample\SymfonyEntityTransfer\Service\AbstractImportExportEntityService;

interface ImportableEntityInterface
{
    /**
     * @return AbstractImportExportEntityService|string
     */
    public static function getEntityImportServiceClass(): string;

    /**
     * @return AbstractEntityImportValidationDto|string
     */
    public static function getEntityImportValidationDtoClass(): string;

    /**
     * @return AbstractEntityImportDto|string
     */
    public static function getEntityImportDtoClass(): string;
}
