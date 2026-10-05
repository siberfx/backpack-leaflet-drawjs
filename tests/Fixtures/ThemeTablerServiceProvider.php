<?php

namespace Siberfx\LeafletDrawjs\Tests\Fixtures;

use Backpack\ThemeTabler\AddonServiceProvider;

/**
 * The theme resolves its files from base_path('vendor/...'), which under Testbench
 * is the skeleton app, so point it at this package's vendor directory instead.
 */
class ThemeTablerServiceProvider extends AddonServiceProvider
{
    public function boot(): void
    {
        $this->path = dirname((new \ReflectionClass(AddonServiceProvider::class))->getFileName(), 2);

        parent::boot();
    }
}
