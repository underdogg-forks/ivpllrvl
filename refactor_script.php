<?php
// Script to refactor controller tests - replace patterns

$files = [
    'ImportControllerTest.php',
    'LayoutControllerTest.php',
    'MailerControllerTest.php',
    'ReportsControllerTest.php',
    'SetupControllerTest.php',
    'UploadControllerTest.php',
    'VersionsControllerTest.php',
    'WelcomeControllerTest.php'
];

$basePath = '/home/runner/work/ivpllrvl/ivpllrvl/modules/core/tests/';

foreach ($files as $file) {
    $path = $basePath . $file;
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    
    // Replace controller instantiation + method call with HTTP tests
    $content = preg_replace(
        '/\$controller = \$this->getController\(\);\s*\$controller->index\(\);/',
        '$response = $this->get(\'/import\');',
        $content
    );
    
    // Replace ob_start/ob_get_clean patterns
    $content = preg_replace(
        '/\$controller = \$this->getController\(\);\s*ob_start\(\);\s*\$controller->(\w+)\(\);\s*\$output = ob_get_clean\(\);/',
        '$response = $this->get(\'/route/$1\');',
        $content
    );
    
    // Replace assertRedirectedTo
    $content = preg_replace(
        '/\$this->assertRedirectedTo\(([^)]+)\);/',
        '$response->assertStatus(302);',
        $content
    );
    
    // Replace assertResponseContains
    $content = preg_replace(
        '/\$this->assertResponseContains\(([^)]+)\);/',
        '$response->assertSee($1);',
        $content
    );
    
    // Replace assertResponseOk
    $content = str_replace(
        '$this->assertResponseOk();',
        '$response->assertOk();',
        $content
    );
    
    file_put_contents($path, $content);
    echo "Refactored: $file\n";
}
