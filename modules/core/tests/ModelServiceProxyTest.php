<?php

namespace Modules\Core\Tests;

use Modules\Core\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ModelServiceProxyTest extends TestCase
{
    #[Test]
    public function every_model_is_a_thin_proxy_to_its_service_counterpart(): void
    {
        $modulesPath = dirname(__DIR__, 3) . '/modules';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modulesPath));

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            if (! str_contains($path, '/src/Models/') || ! str_ends_with($path, '.php')) {
                continue;
            }

            $contents = file_get_contents($path);

            self::assertNotFalse($contents, 'Unable to read model file: ' . $path);
            self::assertMatchesRegularExpression('/class\s+\w+\s+extends\s+\w+Service/', $contents, 'Model should extend a service: ' . $path);
            self::assertMatchesRegularExpression('/public\s+\$table\s*=/', $contents, 'Model should define table name: ' . $path);
            self::assertMatchesRegularExpression('/public\s+\$primary_key\s*=/', $contents, 'Model should define primary key: ' . $path);
            self::assertMatchesRegularExpression('/public\s+\$timestamps\s*=\s*(true|false)\s*;/', $contents, 'Model should define timestamps flag: ' . $path);
            self::assertSame(0, preg_match_all('/function\s+\w+\s*\(/', $contents), 'Model should not declare methods: ' . $path);
        }
    }
}
