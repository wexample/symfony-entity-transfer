<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Dto;

use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Type;
use Wexample\SymfonyApi\Api\Attribute\RequiredDtoProperty;
use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Shelf;

class ImportShelfValidationDto extends AbstractEntityImportValidationDto
{
    #[Type('string')]
    #[RequiredDtoProperty]
    public string $name;

    #[Type('string')]
    #[RequiredDtoProperty]
    public string $title;

    /**
     * @var ImportBookValidationDto[]
     */
    #[Type('array')]
    #[All([new Type(ImportBookValidationDto::class)])]
    public array $book = [];

    public static function getEntityClassName(): string
    {
        return Shelf::class;
    }
}
