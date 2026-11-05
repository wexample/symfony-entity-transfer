<?php

namespace Wexample\SymfonyEntityTransfer\Service;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\Helpers\Helper\TextHelper;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * Service locator for import/export entity services
 *
 * This class helps avoid circular dependencies between import services
 * by providing a central point to access all import services.
 */
class ImportExportServiceLocator
{
    private ContainerInterface $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * Get an import service by entity class
     *
     * @param string|AbstractEntity $entityClass The entity class (e.g., Model::class)
     * @return AbstractImportExportEntityService The import service for the specified entity
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getService(string|AbstractEntity $entityClass): AbstractImportExportEntityService
    {
        if (is_object($entityClass)) {
            $entityClass = ClassHelper::getRealClassPath($entityClass);
        }

        // Normalize the entity class name to ensure consistent lookup
        $entityClass = ltrim($entityClass, '\\');

        // If the service exists directly, return it
        if ($this->locator->has($entityClass)) {
            return $this->locator->get($entityClass);
        }

        // If we get here, no service was found
        $availableServices = [];
        foreach ($this->locator->getProvidedServices() as $key => $value) {
            $availableServices[] = $key;
        }

        throw new \InvalidArgumentException(sprintf(
            'No import service found for entity class "%s". Available services: %s',
            $entityClass,
            implode(', ', $availableServices)
        ));
    }

    /**
     * The service of the entity a file names (`entity: content_type`), found
     * among the entities served — wherever their classes live.
     */
    public function getServiceByEntityName(string $entityName): AbstractImportExportEntityService
    {
        $wanted = TextHelper::toSnake($entityName);

        foreach (array_keys($this->locator->getProvidedServices()) as $entityClass) {
            if (TextHelper::toSnake(ClassHelper::getShortName($entityClass)) === $wanted) {
                return $this->locator->get($entityClass);
            }
        }

        throw new \InvalidArgumentException(sprintf('No import service found for entity "%s".', $entityName));
    }
}
