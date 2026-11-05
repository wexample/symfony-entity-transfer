<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Dto;

use Symfony\Component\Validator\Constraints\Type;
use Wexample\SymfonyApi\Api\Attribute\RequiredDtoProperty;
use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Book;

/**
 * A book, with its shelf named by its unique name, its `_ref` in the same
 * file, its id — or none when the book comes inside its shelf.
 */
class ImportBookValidationDto extends AbstractEntityImportValidationDto
{
    #[Type('string')]
    #[RequiredDtoProperty]
    public string $title;

    #[Type('string')]
    public ?string $code = null;

    #[Type('string')]
    public ?string $shelf_name = null;

    #[Type('string')]
    public ?string $shelf_ref = null;

    #[Type('string')]
    public ?string $shelf_id = null;

    public static function getEntityClassName(): string
    {
        return Book::class;
    }
}
