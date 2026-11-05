<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Dto;

use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportDto;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Book;

class ImportBookDto extends AbstractEntityImportDto
{
    public string $title;

    public ?string $code = null;

    public ?ImportShelfDto $shelf = null;

    public static function getEntityClassName(): string
    {
        return Book::class;
    }
}
