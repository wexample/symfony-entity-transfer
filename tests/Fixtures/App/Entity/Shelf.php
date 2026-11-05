<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyEntityTransfer\Interface\ImportableEntityInterface;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Dto\ImportShelfDto;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Dto\ImportShelfValidationDto;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Repository\ShelfRepository;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Service\ShelfImportExportEntityService;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * A shelf, known by its name, holding books.
 */
#[ORM\Entity(repositoryClass: ShelfRepository::class)]
class Shelf extends AbstractEntity implements ImportableEntityInterface
{
    #[ORM\Column(length: 64, unique: true)]
    public string $name;

    #[ORM\Column(length: 255)]
    public string $title;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'shelf')]
    public Collection $books;

    public function __construct()
    {
        parent::__construct();
        $this->books = new ArrayCollection();
    }

    public static function getEntityImportServiceClass(): string
    {
        return ShelfImportExportEntityService::class;
    }

    public static function getEntityImportValidationDtoClass(): string
    {
        return ImportShelfValidationDto::class;
    }

    public static function getEntityImportDtoClass(): string
    {
        return ImportShelfDto::class;
    }
}
