<?php

namespace Voorhof\Etiquette;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class EtiquetteServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Traits don't need registration as they are loaded through composer's autoloading
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // No need to bootstrap anything for traits
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return []; // No services to provide as traits are not services

    }
}
