<?php

namespace Voorhof\Etiquette\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Voorhof\Etiquette\Traits\ComposerOperations;
use Voorhof\Etiquette\Traits\FileOperations;
use Voorhof\Etiquette\Traits\NodePackageOperations;
use Voorhof\Etiquette\Traits\TestFrameworkOperations;

class TraitsTest extends TestCase
{
    use ComposerOperations;
    use FileOperations;
    use NodePackageOperations;
    use TestFrameworkOperations;

    public function testTraitsExist(): void
    {
        $this->assertTrue(trait_exists(ComposerOperations::class));
        $this->assertTrue(trait_exists(FileOperations::class));
        $this->assertTrue(trait_exists(NodePackageOperations::class));
        $this->assertTrue(trait_exists(TestFrameworkOperations::class));
    }
}
