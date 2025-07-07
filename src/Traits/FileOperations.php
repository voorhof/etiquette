<?php

namespace Voorhof\Etiquette\Traits;

use Illuminate\Filesystem\Filesystem;

/**
 * File Operations Trait
 *
 * Manages file copy during Bries installation.
 */
trait FileOperations
{
    private Filesystem $filesystem;

    protected function initializeFileSystem(): void
    {
        $this->filesystem = new Filesystem;
    }

    /**
     * Copy files to their respective locations.
     *
     * @return bool Success status
     */
    protected function copyFiles(string $origin, string $target, string $file): bool
    {
        $this->initializeFileSystem();

        $this->filesystem->ensureDirectoryExists($target);
        copy($origin.$file, $target.$file);

        return true;
    }

    /**
     * Copy folder to their respective locations.
     *
     * @return bool Success status
     */
    protected function copyFolders(string $origin, string $target): bool
    {
        $this->initializeFileSystem();

        $this->filesystem->ensureDirectoryExists($target);
        $this->filesystem->copyDirectory($origin, $target);

        return true;
    }

    /**
     * Replace a given string within a file.
     *
     * @param  string  $search  Search string
     * @param  string  $replace  Replacement string
     * @param  string  $path  File path
     *
     * @return bool Success status
     */
    protected function replaceInFile(string $search, string $replace, string $path): bool
    {
        file_put_contents($path, str_replace($search, $replace, file_get_contents($path)));

        return true;
    }
}
