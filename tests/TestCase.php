<?php

namespace Voorhof\Etiquette\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            \Voorhof\Etiquette\EtiquetteServiceProvider::class,
        ];
    }
}
