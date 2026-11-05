<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Wexample\SymfonyEntityTransfer\Class\TransferReport;
use Wexample\SymfonyEntityTransfer\Exception\ImportUniqueFieldNotFoundException;
use Wexample\SymfonyEntityTransfer\Helper\ReferenceHelper;
use Wexample\SymfonyEntityTransfer\Service\ImportExportServiceLocator;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Book;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Shelf;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Service\LibraryCollectionService;
use Wexample\Helpers\Helper\ArrayHelper;

/**
 * Files of shelves and books imported, exported back, a root at a time.
 */
class EntityTransferTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        (new SchemaTool($this->entityManager))->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
    }

    public function testAShelfAndItsBooksExportedAsImported(): void
    {
        $shelfData = $this->shelf('poetry', ['Odes', 'Elegies']);
        [$shelf] = $this->collection()->importData(['version' => '1', 'type' => 'single', 'entity' => 'shelf', 'data' => $shelfData]);

        $this->assertInstanceOf(Shelf::class, $shelf);
        $this->assertSame(2, $this->rowsOf(Book::class));

        $this->entityManager->clear();
        $shelf = $this->entityManager->getRepository(Shelf::class)->findOneBy(['name' => 'poetry']);
        $exported = $this->locator()->getService(Shelf::class)->export($shelf);

        $this->assertEquals(
            ArrayHelper::canonicalize(ReferenceHelper::removeReferences($shelfData)),
            ArrayHelper::canonicalize(ReferenceHelper::removeReferences($exported))
        );
    }

    public function testABookFindsItsShelfByItsUniqueName(): void
    {
        $this->collection()->importData(['version' => '1', 'type' => 'single', 'entity' => 'shelf', 'data' => $this->shelf('poetry', [])]);
        $this->collection()->importData(['version' => '1', 'type' => 'single', 'entity' => 'book', 'data' => ['_ref' => 'b1', 'title' => 'Sonnets', 'shelf_name' => 'poetry']]);

        $book = $this->entityManager->getRepository(Book::class)->findOneBy(['title' => 'Sonnets']);
        $this->assertSame('poetry', $book->shelf->name);

        $this->expectException(ImportUniqueFieldNotFoundException::class);
        $this->collection()->importData(['version' => '1', 'type' => 'single', 'entity' => 'book', 'data' => ['_ref' => 'b2', 'title' => 'Lost', 'shelf_name' => 'nowhere']]);
    }

    public function testEachRootImportedWholeOrNotAtAll(): void
    {
        $report = $this->collection()->transfer($this->shelves([
            $this->shelf('first', ['A'], 'c1'),
            // Its second book takes the first shelf's code: the flush fails
            // once the shelf and its first book are written.
            $this->shelf('second', ['B', 'C'], 'c2', ['C' => 'c1-A']),
            $this->shelf('third', ['D'], 'c3'),
        ]));

        $this->assertSame([TransferReport::OUTCOME_IMPORTED, TransferReport::OUTCOME_FAILED, TransferReport::OUTCOME_IMPORTED], array_column($report->getRoots(), 'outcome'));
        $this->assertSame(['imported' => 2, 'failed' => 1], $report->getSummary());
        $this->assertFalse($report->isSuccessful());

        // Nothing of the second shelf left: not the shelf, not its first book.
        $this->assertSame(['first', 'third'], $this->names());
        $this->assertSame(['A', 'D'], $this->titles());
    }

    public function testADryRunReportsAndWritesNothing(): void
    {
        $report = $this->collection()->transfer($this->shelves([$this->shelf('first', ['A']), $this->shelf('second', ['B'])]), dryRun: true);

        $this->assertTrue($report->dryRun);
        $this->assertSame(['imported' => 2, 'failed' => 0], $report->getSummary());
        $this->assertSame(0, $this->rowsOf(Shelf::class));
        $this->assertSame(0, $this->rowsOf(Book::class));
    }

    public function testImportDataStopsOnTheFirstFailure(): void
    {
        try {
            $this->collection()->importData($this->shelves([
                $this->shelf('first', ['A'], 'c1'),
                $this->shelf('second', ['B'], 'c2', ['B' => 'c1-A']),
                $this->shelf('third', ['D'], 'c3'),
            ]));
            $this->fail('The failure was not thrown.');
        } catch (\Throwable) {
        }

        $this->assertSame(['first'], $this->names());
    }

    public function testAnOlderFileIsUpcastBeforeItIsImported(): void
    {
        $this->collection()->importData(['version' => '0.9', 'type' => 'single', 'entity' => 'shelf', 'data' => ['_ref' => 's', 'name' => 'old', 'label' => 'Old shelf']]);

        $this->assertSame('Old shelf', $this->entityManager->getRepository(Shelf::class)->findOneBy(['name' => 'old'])->title);
    }

    private function shelf(string $name, array $titles, ?string $codePrefix = null, array $codes = []): array
    {
        return [
            '_ref' => 'shelf-' . $name,
            'name' => $name,
            'title' => ucfirst($name),
            'book' => array_map(fn (string $title) => [
                '_ref' => 'book-' . $name . '-' . $title,
                'title' => $title,
                'code' => $codes[$title] ?? ($codePrefix ? $codePrefix . '-' . $title : null),
            ], $titles),
        ];
    }

    private function shelves(array $shelves): array
    {
        return ['version' => '1', 'type' => 'collection', 'entity' => 'shelf', 'data' => $shelves];
    }

    private function names(): array
    {
        $names = array_map(fn (Shelf $shelf) => $shelf->name, $this->entityManager->getRepository(Shelf::class)->findAll());
        sort($names);

        return $names;
    }

    private function titles(): array
    {
        $titles = array_map(fn (Book $book) => $book->title, $this->entityManager->getRepository(Book::class)->findAll());
        sort($titles);

        return $titles;
    }

    private function rowsOf(string $class): int
    {
        return $this->entityManager->getRepository($class)->count([]);
    }

    private function collection(): LibraryCollectionService
    {
        return self::getContainer()->get('test.collection_service');
    }

    private function locator(): ImportExportServiceLocator
    {
        return self::getContainer()->get(ImportExportServiceLocator::class);
    }
}
