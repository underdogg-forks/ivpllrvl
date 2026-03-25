<?php

namespace Modules\Invoices\Tests;

use Modules\Core\Providers\ModuleResourceRegistry;
use Modules\Core\Testing\LaravelStyleTestCase;
use PHPUnit\Framework\Attributes\Test;

class InvoiceModuleBootTest extends LaravelStyleTestCase
{
    #[Test]
    public function it_resolves_invoice_views_from_the_real_module_layout(): void
    {
        /* Arrange */
        $resourceRegistry = new ModuleResourceRegistry();
        $modulesPath = dirname(__DIR__, 3) . '/modules/';

        /* Act */
        $viewDirectory = $resourceRegistry->resolveDirectoryForModuleAtLocation('invoices', 'views/', $modulesPath);
        $legacyViewDirectory = $resourceRegistry->resolveDirectoryForModuleAtLocation('invoices', 'legacy-views/', $modulesPath);

        /* Assert */
        self::assertIsString($viewDirectory);
        self::assertStringEndsWith('/modules/invoices/resources/views/', $viewDirectory);
        self::assertDirectoryExists($viewDirectory);
        self::assertNull($legacyViewDirectory);
    }
}
