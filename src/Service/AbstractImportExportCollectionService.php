<?php

namespace Wexample\SymfonyEntityTransfer\Service;

use Doctrine\Persistence\ManagerRegistry;
use Throwable;
use Wexample\SymfonyEntityTransfer\Class\TransferReport;
use Wexample\SymfonyEntityTransfer\Dto\File\AbstractImportRootDto;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Helper\JsonHelper;

/**
 * Imports the files of the application's entities: one entity
 * (`type: single`), or several of one type (`type: collection` with an
 * `entity`) — each **root** in a transaction of its own, imported whole or
 * not at all, its references connected before it commits.
 *
 * A file of several types at once is the application's to read
 * (importOtherData()): which types, in which order.
 */
abstract class AbstractImportExportCollectionService
{
    public function __construct(
        protected readonly ImportReferencesBagService $referencesBagService,
        protected readonly ImportExportServiceLocator $serviceLocator,
        protected readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * The entity types whose references are connected once the entities are
     * created, in the order their dependencies need.
     *
     * @return list<class-string<AbstractEntity>>
     */
    abstract protected function getConnectReferencesOrder(): array;

    /**
     * A file of another shape — several entity types: the application's.
     *
     * @return list<AbstractEntity>
     */
    protected function importOtherData(array $data): array
    {
        return [];
    }

    /**
     * @return list<AbstractEntity>
     *
     * @throws Throwable The first root's failure; the roots before it stay imported.
     */
    public function importJson(string $filePath): array
    {
        return ($data = JsonHelper::readOrNull($filePath, true)) ? $this->importData($data) : [];
    }

    /**
     * @return list<AbstractEntity>
     *
     * @throws Throwable The first root's failure; the roots before it stay imported.
     */
    public function importData(array $data): array
    {
        $report = $this->transfer($data, stopOnFailure: true);

        if ($error = $report->getFirstError()) {
            throw $error;
        }

        return $report->getEntities();
    }

    /**
     * Imports every root of the file, each in its own transaction; a failing
     * root is reported, the others imported. A dry run rolls each back: the
     * report says what the import would do.
     */
    public function transfer(
        array $data,
        bool $dryRun = false,
        bool $stopOnFailure = false,
    ): TransferReport {
        $report = new TransferReport($dryRun);
        $entityName = $data['entity'] ?? null;
        $type = $data['type'] ?? null;

        if (null === $entityName) {
            $this->transferRoot($report, 0, fn () => $this->importOtherData($data), $dryRun);

            return $report;
        }

        $service = $this->serviceLocator->getServiceByEntityName($entityName);
        $roots = AbstractImportRootDto::TYPE_COLLECTION === $type && is_array($data['data'] ?? null)
            ? array_values($data['data'])
            : (AbstractImportRootDto::TYPE_SINGLE === $type ? [$data['data'] ?? []] : []);

        foreach ($roots as $index => $root) {
            $imported = $this->transferRoot(
                $report,
                $index,
                fn () => array_filter([$service->import($root)]),
                $dryRun
            );

            if (! $imported && $stopOnFailure) {
                break;
            }
        }

        return $report;
    }

    public function connectReferences(): void
    {
        foreach ($this->getConnectReferencesOrder() as $entityClass) {
            $this->serviceLocator->getService($entityClass)->connectReferencesCollection();
        }
    }

    /**
     * @param callable(): list<AbstractEntity> $import
     */
    private function transferRoot(
        TransferReport $report,
        int $index,
        callable $import,
        bool $dryRun
    ): bool {
        $connection = $this->managerRegistry->getConnection();
        $this->referencesBagService->reset();
        $connection->beginTransaction();

        try {
            $entities = array_values($import());
            $this->connectReferences();
            $this->managerRegistry->getManager()->flush();

            $dryRun ? $connection->rollBack() : $connection->commit();
            $report->addImported($index, $entities);

            if ($dryRun) {
                $this->managerRegistry->getManager()->clear();
            }

            return true;
        } catch (Throwable $error) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            // A failed flush closes the entity manager: the next root gets a
            // fresh one, nothing of this one left in it.
            $this->managerRegistry->resetManager();
            $report->addFailed($index, $error);

            return false;
        } finally {
            $this->referencesBagService->reset();
        }
    }
}
