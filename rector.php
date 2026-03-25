<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Namespace_\AddNamespaceRector;
use Rector\Config\RectorConfig;
use Rector\PSR4\Rector\Namespace_\NormalizeNamespaceByPSR4ComposerAutoloadRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/modules',
    ]);

    $rectorConfig->rule(AddNamespaceRector::class);
    $rectorConfig->rule(NormalizeNamespaceByPSR4ComposerAutoloadRector::class);
};
