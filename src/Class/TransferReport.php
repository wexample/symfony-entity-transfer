<?php

namespace Wexample\SymfonyEntityTransfer\Class;

use Throwable;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

/**
 * What a transfer did, root by root: each imported whole or not at all.
 */
final class TransferReport
{
    final public const string OUTCOME_IMPORTED = 'imported';

    final public const string OUTCOME_FAILED = 'failed';

    /**
     * @var list<array{index: int, outcome: string, entities: list<AbstractEntity>, error: ?Throwable}>
     */
    private array $roots = [];

    public function __construct(
        public readonly bool $dryRun = false,
    ) {
    }

    /**
     * @param list<AbstractEntity> $entities
     */
    public function addImported(int $index, array $entities): void
    {
        $this->roots[] = ['index' => $index, 'outcome' => self::OUTCOME_IMPORTED, 'entities' => $entities, 'error' => null];
    }

    public function addFailed(int $index, Throwable $error): void
    {
        $this->roots[] = ['index' => $index, 'outcome' => self::OUTCOME_FAILED, 'entities' => [], 'error' => $error];
    }

    /**
     * @return list<array{index: int, outcome: string, entities: list<AbstractEntity>, error: ?Throwable}>
     */
    public function getRoots(): array
    {
        return $this->roots;
    }

    /**
     * The roots imported — in a dry run, those that would have been.
     *
     * @return list<AbstractEntity>
     */
    public function getEntities(): array
    {
        return array_merge(...array_column($this->roots, 'entities'));
    }

    public function getFirstError(): ?Throwable
    {
        foreach ($this->roots as $root) {
            if ($root['error']) {
                return $root['error'];
            }
        }

        return null;
    }

    public function isSuccessful(): bool
    {
        return null === $this->getFirstError();
    }

    /**
     * @return array{imported: int, failed: int}
     */
    public function getSummary(): array
    {
        $outcomes = array_count_values(array_column($this->roots, 'outcome'));

        return [
            self::OUTCOME_IMPORTED => $outcomes[self::OUTCOME_IMPORTED] ?? 0,
            self::OUTCOME_FAILED => $outcomes[self::OUTCOME_FAILED] ?? 0,
        ];
    }
}
