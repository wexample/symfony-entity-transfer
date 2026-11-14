<?php

declare(strict_types=1);

namespace Wexample\SymfonyEntityTransfer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\SymfonyEntityTransfer\Exception\ImportExportNonUniqueReferenceException;
use Wexample\SymfonyEntityTransfer\Service\ExportReferencesBagService;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Book;
use Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Entity\Shelf;

/**
 * The references of an export: one name per entity, found again from the
 * entity — the same instance or another holding its id —, emptied between
 * two exports.
 */
class ExportReferencesBagServiceTest extends TestCase
{
    private ExportReferencesBagService $bag;

    protected function setUp(): void
    {
        $this->bag = new ExportReferencesBagService();
    }

    public function testAReferenceIsFoundByNameAndByEntity(): void
    {
        $book = new Book();
        $this->bag->addReference($book, 'dune');

        $this->assertSame($book, $this->bag->getReference('dune', Book::class));
        $this->assertSame($book, $this->bag->getByFullReference('book:dune'));
        $this->assertSame('dune', $this->bag->getReferenceName($book));
        $this->assertSame(['book:dune'], $this->bag->listReferences());
        $this->assertSame(['dune' => $book], $this->bag->getReferencesForEntityType($book));
    }

    public function testAnotherInstanceOfTheSameEntityGoesByItsName(): void
    {
        $book = new Book();
        $this->bag->addReference($book, 'dune');

        $copy = new Book();
        $copy->setId($book->getId());

        $this->assertSame('dune', $this->bag->getReferenceName($copy));
    }

    public function testAnEntityNeverRegisteredHasNoName(): void
    {
        $this->assertNull($this->bag->getReferenceName(new Book()));

        $this->bag->addReference(new Book(), 'dune');

        $this->assertNull($this->bag->getReferenceName(new Book()));
        // Another class, the same id: another entity.
        $shelf = new Shelf();
        $shelf->setId($this->bag->getReference('dune', Book::class)->getId());
        $this->assertNull($this->bag->getReferenceName($shelf));
    }

    public function testEachOfManyEntitiesIsFoundAsTheyAreAdded(): void
    {
        $books = [];

        for ($i = 0; $i < 50; ++$i) {
            $books[$i] = new Book();
            $this->bag->addReference($books[$i], 'book-' . $i);

            // Looked for between two additions, as an export does.
            $this->assertSame('book-' . $i, $this->bag->getReferenceName($books[$i]));
        }

        foreach ($books as $i => $book) {
            $this->assertSame('book-' . $i, $this->bag->getReferenceName($book));
        }
    }

    public function testAnEntityRegisteredTwiceGoesByItsFirstName(): void
    {
        $book = new Book();
        $this->bag->addReference($book, 'first');
        $this->bag->addReference($book, 'second');

        $this->assertSame('first', $this->bag->getReferenceName($book));
    }

    public function testANameIsGivenOnce(): void
    {
        $this->bag->addReference(new Book(), 'dune');

        $this->expectException(ImportExportNonUniqueReferenceException::class);
        $this->bag->addReference(new Book(), 'dune');
    }

    public function testANameGivenOnceIsKeptByTheSameEntity(): void
    {
        $book = new Book();
        $name = $this->bag->getOrCreateExportReferenceName($book, asExisting: false);

        $this->assertSame($name, $this->bag->getOrCreateExportReferenceName($book, asExisting: false));
        $this->assertNotSame($name, $this->bag->getOrCreateExportReferenceName(new Book(), asExisting: false));
        $this->assertCount(2, $this->bag->listReferences());
    }

    public function testAnExistingEntityIsNamedAfterItsId(): void
    {
        $book = new Book();

        $this->assertSame('existing-book-' . $book->getId(), $this->bag->getOrCreateExportReferenceName($book, asExisting: true));
        $this->assertSame('existing-book-' . $book->getId(), $this->bag->getOrCreateExportReferenceName($book, asExisting: true));
        $this->assertCount(1, $this->bag->listReferences());
    }

    public function testResetForgetsEverything(): void
    {
        $book = new Book();
        $this->bag->addReference($book, 'dune');
        $this->assertSame('dune', $this->bag->getReferenceName($book));

        $this->bag->reset();

        $this->assertNull($this->bag->getReference('dune', Book::class));
        $this->assertNull($this->bag->getReferenceName($book));
        $this->assertSame([], $this->bag->listReferences());

        // Registered again after a reset, under another name.
        $this->bag->addReference($book, 'again');
        $this->assertSame('again', $this->bag->getReferenceName($book));
    }
}
