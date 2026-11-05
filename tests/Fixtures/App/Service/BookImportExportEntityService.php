<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Service;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Wexample\SymfonyApi\Service\DtoValidationService;
use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportDto;
use Wexample\SymfonyEntityTransfer\Dto\AbstractImportValidationDto;
use Wexample\SymfonyEntityTransfer\Service\AbstractImportExportEntityService;
use Wexample\SymfonyEntityTransfer\Service\ImportExportServiceLocator;
use Wexample\SymfonyEntityTransfer\Service\ImportReferencesBagService;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Dto\ImportBookDto;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Book;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Shelf;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Normalizer\ExportBookNormalizer;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Repository\BookRepository;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

class BookImportExportEntityService extends AbstractImportExportEntityService
{
    public function __construct(
        DtoValidationService $dtoValidationService,
        BookRepository $repository,
        ExportBookNormalizer $normalizer,
        ImportReferencesBagService $referencesBag,
        AuthorizationCheckerInterface $auth,
        ImportExportServiceLocator $serviceLocator
    ) {
        parent::__construct($dtoValidationService, $normalizer, $repository, $referencesBag, $auth, $serviceLocator);
    }

    public static function getEntityClassName(): string
    {
        return Book::class;
    }

    public function createDtoFromValidationDto(
        AbstractImportValidationDto $validationDto,
        bool $allowExisting = false
    ): AbstractEntityImportDto {
        /** @var ImportBookDto $importDto */
        $importDto = parent::createDtoFromValidationDto($validationDto, $allowExisting);
        $importDto->title = $validationDto->title;
        $importDto->code = $validationDto->code;
        // Inside its shelf, the shelf sets it; named, it must be found.
        $importDto->shelf = $this->findImportDtoFromValidationDto(
            Shelf::class,
            $validationDto,
            allowExisting: true,
            required: (bool) ($validationDto->shelf_name ?? $validationDto->shelf_ref ?? $validationDto->shelf_id)
        );

        return $importDto;
    }

    protected function createEntityFromImportDto(AbstractEntityImportDto $importDto): AbstractEntity
    {
        $book = new Book();
        $book->title = $importDto->title;
        $book->code = $importDto->code;
        $book->shelf = $importDto->shelf->getEntity();
        $book->shelf->books->add($book);

        return $book;
    }
}
