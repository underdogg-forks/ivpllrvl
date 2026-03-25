<?php

$envFile = '.env';
$legacyEnvFile = 'ipconfig.php';

if (file_exists(base_path($envFile))) {
    require base_path('vendor/autoload.php');
    Dotenv\Dotenv::createImmutable(base_path(), $envFile)->safeLoad();
} elseif (file_exists(base_path($legacyEnvFile))) {
    require base_path('vendor/autoload.php');
    Dotenv\Dotenv::createImmutable(base_path(), $legacyEnvFile)->load();
} else {
    exit('The <b>.env</b> file is missing! You can migrate from <b>ipconfig.php.example</b> to <b>.env</b>.');
}
