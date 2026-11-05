<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyEntityTransfer\Interface\ImportableEntityInterface;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Dto\ImportBookDto;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Dto\ImportBookValidationDto;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Repository\BookRepository;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Service\BookImportExportEntityService;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * A book on a shelf; its code unique, so that a test can make a flush fail.
 */
#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book extends AbstractEntity implements ImportableEntityInterface
{
    #[ORM\Column(length: 255)]
    public string $title;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    public ?string $code = null;

    #[ORM\ManyToOne(targetEntity: Shelf::class, inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: false)]
    public Shelf $shelf;

    public static function getEntityImportServiceClass(): string
    {
        return BookImportExportEntityService::class;
    }

    public static function getEntityImportValidationDtoClass(): string
    {
        return ImportBookValidationDto::class;
    }

    public static function getEntityImportDtoClass(): string
    {
        return ImportBookDto::class;
    }
}
