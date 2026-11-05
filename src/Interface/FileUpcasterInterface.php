<?php

namespace Wexample\SymfonyEntityTransfer\Interface;

/**
 * Brings a file written by an older version of the application to today's
 * shape before it is imported — a key renamed, a value read otherwise —, so
 * that the import services know one shape only. Applied, in the order of
 * their priority tag, by every upcaster supporting the file.
 */
interface FileUpcasterInterface
{
    public function supports(array $file): bool;

    public function upcast(array $file): array;
}
