<?php

namespace Wexample\SymfonyEntityTransfer\Tests\Fixtures\App\Upcaster;

use Wexample\SymfonyEntityTransfer\Interface\FileUpcasterInterface;

/**
 * Version 0 of the library called a shelf's title its label.
 */
class ShelfLabelUpcaster implements FileUpcasterInterface
{
    public function supports(array $file): bool
    {
        return 'shelf' === ($file['entity'] ?? null) && version_compare($file['version'] ?? '0', '1', '<');
    }

    public function upcast(array $file): array
    {

        if ('single' === $file['type']) {
            $file['data'] = $this->upcastShelf($file['data']);
        } else {
            $file['data'] = array_map($this->upcastShelf(...), $file['data']);
        }

        $file['version'] = '1';

        return $file;
    }

    private function upcastShelf(array $shelf): array
    {
        if (array_key_exists('label', $shelf)) {
            $shelf['title'] = $shelf['label'];
            unset($shelf['label']);
        }

        return $shelf;
    }
}
