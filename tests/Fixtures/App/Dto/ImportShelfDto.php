<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Dto;

use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportDto;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Shelf;

class ImportShelfDto extends AbstractEntityImportDto
{
    public string $name;

    public string $title;

    /**
     * @var list<ImportBookDto>
     */
    public array $books = [];

    public static function getEntityClassName(): string
    {
        return Shelf::class;
    }

    public static function getUniqueFieldName(): ?string
    {
        return 'name';
    }
}
