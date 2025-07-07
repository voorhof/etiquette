<?php

namespace Voorhof\Etiquette\Traits;

use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Node Package Operations Trait
 *
 * Handles Node.js package management and asset compilation operations.
 *
 * @property-read OutputInterface $output Console output interface
 */
trait NodePackageOperations
{
    /**
     * Update the dependencies in the "package.json" file.
     *
     * @param  callable  $callback  Function that returns the new package configuration
     * @param  bool  $dev  Whether to update devDependencies or dependencies
     * @return bool Success status
     *
     * @throws RuntimeException When package.json operations fail
     */
    protected static function updateNodePackages(callable $callback, bool $dev = true): bool
    {
        if (! file_exists(base_path('package.json'))) {
            return false;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );

        return true;
    }

    /**
     * Compile the node dependencies with the detected package manager.
     *
     * @return bool Success status
     *
     * @throws RuntimeException When compilation fails
     */
    protected function compileNodePackages(): bool
    {
        if (file_exists(base_path('pnpm-lock.yaml'))) {
            $this->runCommands(['pnpm install', 'pnpm run build']);
        } elseif (file_exists(base_path('yarn.lock'))) {
            $this->runCommands(['yarn install', 'yarn run build']);
        } elseif (file_exists(base_path('bun.lock')) || file_exists(base_path('bun.lockb'))) {
            $this->runCommands(['bun install', 'bun run build']);
        } elseif (file_exists(base_path('deno.lock'))) {
            $this->runCommands(['deno install', 'deno task build']);
        } else {
            $this->runCommands(['npm install', 'npm run build']);
        }

        return true;
    }

    /**
     * Execute shell commands with process management.
     *
     * @param  array  $commands  Array of shell commands to execute
     *
     * @throws RuntimeException When process execution fails
     *
     * Process Steps:
     * 1. Create a shell command process
     * 2. Configure TTY if available
     * 3. Execute and handle output
     */
    protected function runCommands(array $commands): void
    {
        $process = $this->createProcess($commands);

        if ($this->shouldUseTty()) {
            $this->configureTty($process);
        }

        $this->executeProcess($process);
    }

    /**
     * Create a new Process instance for the commands.
     *
     * @param  array  $commands  Commands to be executed
     * @return Process Configured Process instance
     */
    private function createProcess(array $commands): Process
    {
        return Process::fromShellCommandline(
            implode(' && ', $commands),
            null,
            null,
            null,
            null
        );
    }

    /**
     * Configure TTY settings for the process.
     *
     * @param  Process  $process  Process instance to configure
     *
     * @throws RuntimeException When TTY configuration fails
     */
    private function configureTty(Process $process): void
    {
        try {
            $process->setTty(true);
        } catch (RuntimeException $e) {
            $this->output->writeln(
                '  <bg=yellow;fg=black> WARN </> '.$e->getMessage().PHP_EOL
            );
        }
    }

    /**
     * Execute the process and handle its output.
     *
     * @param  Process  $process  Process to execute
     *
     * @throws RuntimeException When process execution fails
     */
    private function executeProcess(Process $process): void
    {
        $process->run(function ($type, $line) {
            $this->output->write('    '.$line);
        });
    }

    /**
     * Determine if TTY should be used for process execution.
     *
     * Checks if:
     * 1. Not running on Windows
     * 2. TTY device exists
     * 3. TTY device is readable
     *
     * @return bool True if TTY should be used
     */
    private function shouldUseTty(): bool
    {
        return '\\' !== DIRECTORY_SEPARATOR
            && file_exists('/dev/tty')
            && is_readable('/dev/tty');
    }
}
