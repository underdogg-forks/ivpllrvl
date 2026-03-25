<?php

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__);

        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

$system_path = base_path('vendor/codeigniter/framework/system');
$application_folder = base_path('modules/core');
