<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Service;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Wexample\SymfonyApi\Service\DtoValidationService;
use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportDto;
use Wexample\SymfonyEntityTransfer\Dto\AbstractImportValidationDto;
use Wexample\SymfonyEntityTransfer\Service\AbstractImportExportEntityService;
use Wexample\SymfonyEntityTransfer\Service\ImportExportServiceLocator;
use Wexample\SymfonyEntityTransfer\Service\ImportReferencesBagService;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Dto\ImportShelfDto;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Shelf;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Normalizer\ExportShelfNormalizer;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Repository\ShelfRepository;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

class ShelfImportExportEntityService extends AbstractImportExportEntityService
{
    public function __construct(
        DtoValidationService $dtoValidationService,
        ShelfRepository $repository,
        ExportShelfNormalizer $normalizer,
        ImportReferencesBagService $referencesBag,
        AuthorizationCheckerInterface $auth,
        ImportExportServiceLocator $serviceLocator
    ) {
        parent::__construct($dtoValidationService, $normalizer, $repository, $referencesBag, $auth, $serviceLocator);
    }

    public static function getEntityClassName(): string
    {
        return Shelf::class;
    }

    public function createDtoFromValidationDto(
        AbstractImportValidationDto $validationDto,
        bool $allowExisting = false
    ): AbstractEntityImportDto {
        /** @var ImportShelfDto $importDto */
        $importDto = parent::createDtoFromValidationDto($validationDto, $allowExisting);
        $importDto->name = $validationDto->name;
        $importDto->title = $validationDto->title;

        foreach ($validationDto->book as $bookValidationDto) {
            $bookDto = $this->createChildDtoFromChildValidationDto($bookValidationDto, $allowExisting);
            $bookDto->shelf = $importDto;
            $importDto->books[] = $bookDto;
        }

        return $importDto;
    }

    public function saveEntityFromImportDto(AbstractEntityImportDto $importDto): ?AbstractEntity
    {
        $shelf = parent::saveEntityFromImportDto($importDto);

        foreach ($importDto->books as $bookDto) {
            $this->saveChildEntityFromImportDto($bookDto);
        }

        return $shelf;
    }

    protected function createEntityFromImportDto(AbstractEntityImportDto $importDto): AbstractEntity
    {
        $shelf = new Shelf();
        $shelf->name = $importDto->name;
        $shelf->title = $importDto->title;

        return $shelf;
    }
}
