<?php

namespace Wexample\SymfonyEntityTransfer\Service;

use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportDto;
use Wexample\SymfonyEntityTransfer\Dto\AbstractEntityImportValidationDto;
use Wexample\SymfonyEntityTransfer\Dto\AbstractImportValidationDto;
use Wexample\SymfonyEntityTransfer\Interface\ImportableEntityInterface;
use Wexample\SymfonyEntityTransfer\Exception\ImportEntityAlreadyExistsException;
use Wexample\SymfonyEntityTransfer\Exception\ImportEntityNotFoundException;
use Wexample\SymfonyEntityTransfer\Exception\ImportInvalidRelationTypeException;
use Wexample\SymfonyEntityTransfer\Exception\ImportReferenceNotFoundException;
use Wexample\SymfonyEntityTransfer\Exception\ImportUndefinedRelationException;
use Wexample\SymfonyEntityTransfer\Exception\ImportUniqueFieldNotFoundException;
use Wexample\SymfonyEntityTransfer\Helper\ReferenceHelper;
use Wexample\SymfonyEntityTransfer\Service\ImportExportServiceLocator;
use Wexample\SymfonyEntityTransfer\Service\ImportReferencesBagService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use JsonException;
use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Wexample\Helpers\Helper\ArrayHelper;
use Wexample\SymfonyApi\Exception\JsonEncodingException;
use Wexample\SymfonyApi\Service\DtoValidationService;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Entity\Traits\Manipulator\EntityManipulatorTrait;
use Wexample\SymfonyHelpers\Normalizer\AbstractLegacyEntityNormalizer;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;

abstract class AbstractImportExportEntityService
{
    use EntityManipulatorTrait;

    protected EntityManagerInterface $entityManager;
    protected ?ImportExportServiceLocator $serviceLocator = null;

    public function __construct(
        protected DtoValidationService $dtoValidationService,
        protected AbstractLegacyEntityNormalizer $normalizer,
        protected AbstractRepository $repository,
        protected ImportReferencesBagService $referencesBag,
        protected AuthorizationCheckerInterface $auth,
        ?ImportExportServiceLocator $serviceLocator = null
    ) {
        // Get the entity manager from the repository
        $this->entityManager = $repository->getEntityManager();
        $this->serviceLocator = $serviceLocator;
    }

    public function getNormalizer(): AbstractLegacyEntityNormalizer
    {
        return $this->normalizer;
    }

    /**
     * @param array $entityData
     * @param bool $unique
     * @return void
     * @throws JsonEncodingException
     * @throws JsonException
     * @throws ReflectionException
     */
    public function import(
        array $entityData,
        bool $unique = false
    ): ?AbstractEntity {
        // Reset references bag before import
        $this->referencesBag->reset();

        /** @var AbstractEntityImportValidationDto $validationDto */
        $validationDto = $this->dtoValidationService->createDto(
            $entityData,
            $this->getEntityClassName()::getEntityImportValidationDtoClass()
        );

        return $this->importFromValidationDto($validationDto, $unique);
    }

    protected function validateValidationDto(AbstractImportValidationDto $validationDto): void
    {
        // Do some extra validation that could not be done into the validation dto creation.
    }

    public function importFromValidationDto(
        AbstractEntityImportValidationDto $validationDto,
        bool $unique = false
    ): ?AbstractEntity {
        if ($unique && $existingEntity = $this->findExistingEntityByUniqueField($validationDto)) {
            // Check for uniqueness if requested
            return $existingEntity;
        }

        return $this->saveEntityFromImportDto(
            $this->createOrGetDtoFromValidationDto(
                $validationDto,
                allowExisting: true
            )
        );
    }

    /**
     * Import entity from a validation DTO
     *
     * This method can be overridden in child classes if needed
     */
    public function saveEntityFromImportDto(
        AbstractEntityImportDto $importDto
    ): ?AbstractEntity {
        // If entity already exists,
        // do not recreate it.
        // It should also be registered.
        if ($entity = $importDto->getEntity()) {
            return $entity;
        }

        if ($entity = $this->createEntityFromImportDto($importDto)) {
            $this->repository->save($entity);
            $this->referencesBag->getImportReference($importDto->_ref, $entity)->setEntity($entity);

            return $entity;
        }

        return null;
    }

    public function saveChildEntityFromImportDto(AbstractEntityImportDto $importDto): AbstractEntity
    {
        if ($entity = $importDto->getEntity()) {
            return $entity;
        }

        return $this->serviceLocator->getService($importDto::getEntityClassName())->saveEntityFromImportDto($importDto);
    }

    /**
     * @throws Exception
     */
    public function createImportDto(
        ?string $ref = null
    ): AbstractEntityImportDto {
        /** @var AbstractEntity|ImportableEntityInterface $entityClass */
        $entityClass = $this::getEntityClassName();
        $importDtoClass = $entityClass::getEntityImportDtoClass();

        /** @var AbstractEntityImportDto $importDto */
        $importDto = new $importDtoClass();
        $importDto->_ref = $ref ?: ReferenceHelper::buildReferenceId();

        $this->referencesBag->addReference(
            $importDto,
            $importDto->_ref
        );

        return $importDto;
    }

    public function createOrGetDtoFromValidationDto(
        AbstractImportValidationDto $validationDto,
        bool $allowExisting = false,
    ): AbstractEntityImportDto {
        if ($validationDto->id) {
            if ($entity = $this->repository->find($validationDto->id)) {
                if (! $allowExisting) {
                    throw new ImportEntityAlreadyExistsException($validationDto);
                }

                return $this->createExistingDtoFromEntity(
                    $entity
                );
            }
        }

        return $this->createDtoFromValidationDto(
            $validationDto,
            $allowExisting
        );
    }

    /**
     * Create an import DTO from a validation DTO
     *
     * This method must be implemented by child classes to transform
     * validation DTOs into import DTOs.
     *
     * @param AbstractEntityImportValidationDto $validationDto The validated DTO
     * @param bool $allowExisting
     * @return AbstractEntityImportDto The import DTO ready for import processing
     * @throws ImportEntityAlreadyExistsException
     */
    public function createDtoFromValidationDto(
        AbstractImportValidationDto $validationDto,
        bool $allowExisting = false,
    ): AbstractEntityImportDto {
        $this->validateValidationDto($validationDto);

        return $this->createImportDto($validationDto->_ref);
    }

    public function createExistingDtoFromEntity(
        AbstractEntity $entity
    ): AbstractEntityImportDto {
        $ref = ReferenceHelper::buildReferenceId($entity);
        // The dto already exists.
        /** @var AbstractEntityImportDto $existingDto */
        if ($existingDto = $this->referencesBag->getReference(
            $ref,
            $entity
        )) {
            return $existingDto;
        }

        // Simulate an export / import to get a valid version.
        $export = $this->export(
            $entity,
            asExisting: true,
            exportChildren: false
        );

        // Hydrate the local dto to allow it behave like an imported one.
        /** @var AbstractEntityImportValidationDto $validationDto */
        $validationDto = $this->dtoValidationService->createDto(
            $export,
            $this->getEntityClassName()::getEntityImportValidationDtoClass()
        );

        $created = $this->createDtoFromValidationDto(
            $validationDto,
            allowExisting: true
        );

        $created->setEntity($entity);

        return $created;
    }

    /**
     * Create a new entity from an import DTO
     *
     * This method creates an entity from an import DTO, handling all the
     * necessary transformations and associations.
     *
     * @param AbstractEntityImportDto $importDto The import DTO
     * @return AbstractEntity The created entity
     */
    abstract protected function createEntityFromImportDto(AbstractEntityImportDto $importDto): AbstractEntity;

    /**
     * Find an existing entity by its unique field
     *
     * @param AbstractEntityImportValidationDto $validationDto
     * @return AbstractEntity|null The existing entity or null if not found
     */
    protected function findExistingEntityByUniqueField(
        AbstractEntityImportValidationDto $validationDto
    ): ?AbstractEntity {
        $entityClassName = $this::getEntityClassName();
        $importDtoClass = $entityClassName::getEntityImportDtoClass();

        if (! $entityFieldName = $importDtoClass::getUniqueFieldName()) {
            return null;
        }

        // If the unique field is not in the data, we can't check for uniqueness
        if (empty($validationDto->$entityFieldName)) {
            return null;
        }

        $uniqueValue = $validationDto->$entityFieldName;
        $criteria = [$entityFieldName => $uniqueValue];

        return $this->repository->findOneBy($criteria);
    }

    /**
     * Export entity to JSON format
     */
    public function export(
        AbstractEntity $entity,
        bool $asExisting = false,
        bool $exportChildren = true
    ): array {
        return ArrayHelper::canonicalize(
            $this->normalizer->normalize(
                $entity,
                context: [
                    'asExisting' => $asExisting,
                    'exportChildren' => $exportChildren,
                ]
            )
        );
    }

    /**
     * Connect references for an entity after all entities have been created
     *
     * This method should be overridden in child classes that need to handle references
     * that might not be available during the initial import phase.
     *
     * @param AbstractEntityImportDto $importDto The validation DTO
     * @return AbstractEntity The entity with connected references
     */
    public function connectReferences(
        AbstractEntityImportDto $importDto
    ): void {
        // By default, do nothing - child classes should override this method if needed
    }

    public function connectReferencesCollection()
    {
        /** @var AbstractEntityImportDto $importDto */
        foreach ($this->referencesBag->getReferencesForEntityType($this->getEntityClassName()) as $importDto) {
            // Connect references for all entities of this type
            $this->connectReferences($importDto);
        }
    }

    /**
     * @param string|AbstractEntity|ImportableEntityInterface $entityClassName
     * @param AbstractEntityImportValidationDto $validationDto
     * @param bool $allowExisting
     * @param string|null $relationEntityFieldName
     * @param bool $required
     * @return AbstractEntityImportDto|null
     * @throws ImportEntityNotFoundException
     * @throws ImportInvalidRelationTypeException
     * @throws ImportReferenceNotFoundException
     * @throws ImportUndefinedRelationException
     * @throws ImportUniqueFieldNotFoundException
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function findImportDtoFromValidationDto(
        string $entityClassName,
        AbstractEntityImportValidationDto $validationDto,
        bool $allowExisting,
        ?string $relationEntityFieldName = null,
        bool $required = false
    ): null|AbstractEntityImportDto {
        $importDtoClass = $entityClassName::getEntityImportDtoClass();
        $searchField = $importDtoClass::getUniqueFieldName();
        $importDto = null;

        // Find relation value using the helper
        $relationInfo = ReferenceHelper::findRelationValue(
            validationDto: $validationDto,
            targetEntityClass: $entityClassName,
            relationEntityFieldName: $relationEntityFieldName
        );

        if ($relationInfo) {
            $fieldType = $relationInfo->fieldType;
            $fieldValue = $relationInfo->value;

            switch ($fieldType) {
                case ReferenceHelper::RELATION_TYPE_ID:
                    // Find entity by ID
                    if ($existingEntity = $this->entityManager->getRepository($entityClassName)->find($fieldValue)) {
                        return $this->serviceLocator
                            ->getService($existingEntity)
                            ->createExistingDtoFromEntity(
                                $existingEntity
                            );
                    }

                    if ($required) {
                        throw new ImportEntityNotFoundException(
                            entityType: $entityClassName,
                            validationDto: $validationDto,
                            relationInfo: $relationInfo,
                            relationEntityFieldName: $relationEntityFieldName
                        );
                    }

                    break;

                case ReferenceHelper::RELATION_TYPE_UNIQUE:
                    // Find entity by unique field
                    if ($searchField) {

                        /** @var AbstractEntity $existingEntity */
                        if ($existingEntity = $this->entityManager->getRepository($entityClassName)->findOneBy([
                            $searchField => $fieldValue,
                        ])) {
                            return $this->serviceLocator
                                ->getService($existingEntity)
                                ->createExistingDtoFromEntity($existingEntity);
                        }

                        if (! $importDto && $required) {
                            throw new ImportUniqueFieldNotFoundException(
                                entityType: $entityClassName,
                                searchField: $searchField,
                                validationDto: $validationDto,
                                relationInfo: $relationInfo,
                                relationEntityFieldName: $relationEntityFieldName
                            );
                        }
                    } elseif ($required) {
                        throw new LogicException(
                            sprintf('Entity class "%s" does not define a unique field name', $entityClassName)
                        );
                    }

                    break;

                case ReferenceHelper::RELATION_TYPE_REF:
                    // Find entity by reference
                    $importDto = $this->referencesBag->getImportReference($fieldValue, $entityClassName);

                    if (! $importDto && $required) {
                        throw new ImportReferenceNotFoundException(
                            entityType: $entityClassName,
                            validationDto: $validationDto,
                            relationInfo: $relationInfo,
                            referencesBag: $this->referencesBag,
                            relationEntityFieldName: $relationEntityFieldName
                        );
                    }

                    break;

                case ReferenceHelper::RELATION_TYPE_DTO:
                    // Process DTO
                    if ($fieldValue instanceof AbstractEntityImportValidationDto) {
                        $importDto = $this->createChildDtoFromChildValidationDto(
                            $fieldValue,
                            $allowExisting
                        );
                    } elseif ($required) {
                        throw new ImportInvalidRelationTypeException(
                            entityType: $entityClassName,
                            validationDto: $validationDto,
                            relationInfo: $relationInfo,
                            expectedType: AbstractEntityImportValidationDto::class,
                            relationEntityFieldName: $relationEntityFieldName
                        );
                    }

                    break;
            }
        } elseif ($required) {
            throw new ImportUndefinedRelationException(
                entityType: $entityClassName,
                validationDto: $validationDto,
                relationEntityFieldName: $relationEntityFieldName
            );
        }

        return $importDto;
    }

    protected function createChildDtoFromChildValidationDto(
        AbstractEntityImportValidationDto $validationDto,
        bool $allowExisting,
    ): ?AbstractEntityImportDto {
        // Get the appropriate import service using the service locator if available
        return $this
            ->serviceLocator
            ->getService($validationDto::getEntityClassName())
            ->createOrGetDtoFromValidationDto(
                $validationDto,
                $allowExisting
            );
    }

    /**
     * @param string $entityClass
     * @param AbstractEntityImportValidationDto $validationDto
     * @param bool $allowExisting
     * @param string|null $relationEntityFieldName
     * @return AbstractEntityImportDto The related entity if found, null otherwise
     * @throws ImportEntityNotFoundException
     * @throws ImportInvalidRelationTypeException
     * @throws ImportReferenceNotFoundException
     * @throws ImportUndefinedRelationException
     * @throws ImportUniqueFieldNotFoundException
     */
    protected function findImportDtoFromValidationDtoOrFail(
        string $entityClass,
        AbstractEntityImportValidationDto $validationDto,
        bool $allowExisting,
        ?string $relationEntityFieldName = null
    ): AbstractEntityImportDto {
        return $this->findImportDtoFromValidationDto(
            $entityClass,
            $validationDto,
            $allowExisting,
            $relationEntityFieldName,
            true
        );
    }
}
