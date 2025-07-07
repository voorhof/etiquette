<?php

namespace Voorhof\Etiquette\Traits;

use Exception;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Composer Package Management Operations
 *
 * Provides methods for managing Composer dependencies and configuration.
 *
 * @property OutputInterface $output Console output interface
 */
trait ComposerOperations
{
    private array $composerConfig = [];

    /**
     * Check for composer configuration availability
     */
    protected function ensureComposerConfigAvailable(): bool
    {
        if (empty($this->getComposerConfig())) {
            $this->error('Unable to read composer configuration');

            return false;
        }

        return true;
    }

    /**
     * Get cached composer configuration.
     */
    protected function getComposerConfig(): array
    {
        if (empty($this->composerConfig)) {
            try {
                $composerJson = file_get_contents(base_path('composer.json'));
                if ($composerJson === false) {
                    throw new RuntimeException('Unable to read composer.json');
                }

                $this->composerConfig = json_decode($composerJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException('Invalid composer.json format');
                }
            } catch (Exception $e) {
                $this->error("Failed to read composer configuration: {$e->getMessage()}");

                return [];
            }
        }

        return $this->composerConfig;
    }

    /**
     * Check if a package exists in composer.json.
     */
    protected function hasComposerPackage(string $package): bool
    {
        if (! $this->validatePackageName($package)) {
            $this->error("Invalid package name format: $package");

            return false;
        }

        $packages = $this->getComposerConfig();

        return array_key_exists($package, $packages['require'] ?? [])
            || array_key_exists($package, $packages['require-dev'] ?? []);
    }

    protected function hasComposerPackageVersion(string $package, string $version): bool
    {
        try {
            $composerJson = file_get_contents(base_path('composer.json'));
            $packages = json_decode($composerJson, true);

            $requireVersion = $packages['require'][$package] ?? null;
            $requireDevVersion = $packages['require-dev'][$package] ?? null;

            return $this->isVersionCompatible($requireVersion, $version)
                || $this->isVersionCompatible($requireDevVersion, $version);
        } catch (Exception $e) {
            $this->error("Error checking package version: {$e->getMessage()}");

            return false;
        }
    }

    protected function isVersionCompatible(?string $actual, string $required): bool
    {
        if ($actual === null) {
            return false;
        }

        // You might want to use Composer's version parser here
        // This is a simplified example
        return version_compare(
            trim($actual, '^~>=<'),
            trim($required, '^~>=<'),
            '>='
        );
    }

    /**
     * Manage Composer package operations.
     *
     * @param  array<string>  $packages  Packages to manage
     * @param  string  $action  Action to perform ('require'|'remove')
     * @param  bool  $asDev  Install as dev dependency
     * @return bool Operation success status
     *
     * Steps:
     * 1. Validate action type
     * 2. Configure Composer command
     * 3. Execute package operation
     * 4. Handle process output
     *
     * @throws RuntimeException When Composer operation fails
     */
    protected function manageComposerPackages(array $packages, string $action = 'require', bool $asDev = false): bool
    {
        if (! in_array($action, ['require', 'remove'])) {
            $this->error("Invalid composer action: $action");

            return false;
        }

        $composer = $this->option('composer');
        $baseCommand = $composer !== 'global' ? ['php', $composer, $action] : ['composer', $action];

        $command = array_merge(
            $baseCommand,
            array_filter($packages, [$this, 'validatePackageName']),
            $asDev ? ['--dev'] : []
        );

        $process = new Process(
            $command,
            base_path(),
            ['COMPOSER_MEMORY_LIMIT' => '-1']
        );

        $process->setTimeout(null);

        try {
            $result = $process->run(function ($type, $output) {
                $this->output->write($output);
            });

            if ($result !== 0) {
                $this->error("Composer $action command failed");

                return false;
            }

            // Clear the cache after modification
            $this->composerConfig = [];

            return true;
        } catch (Exception $e) {
            $this->error("Error during composer $action: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Install Composer packages.
     */
    protected function requireComposerPackages(array $packages, bool $asDev = false): bool
    {
        return $this->manageComposerPackages($packages, 'require', $asDev);
    }

    /**
     * Remove Composer packages.
     */
    protected function removeComposerPackages(array $packages, bool $asDev = false): bool
    {
        return $this->manageComposerPackages($packages, 'remove', $asDev);
    }

    /**
     * Validate package name format.
     */
    private function validatePackageName(string $package): bool
    {
        return (bool) preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$/', $package);
    }
}
