<?php

if (strpos(basename(__FILE__), 'phar')) {
    require_once 'phar://acquiacli.phar/vendor/autoload.php';
} else {
    if (file_exists(__DIR__.'/vendor/autoload.php')) {
        require_once __DIR__.'/vendor/autoload.php';
    } elseif (file_exists(__DIR__.'/../../../autoload.php')) {
        require_once __DIR__ . '/../../../autoload.php';
    } else {
        require_once 'phar://acquiacli.phar/vendor/autoload.php';
    }
}

$discovery = new \Consolidation\AnnotatedCommand\CommandFileDiscovery();
$discovery->setSearchPattern('*Command.php');
$commandClasses = $discovery->discover(dirname(__DIR__) . '/src/Commands', '\AcquiaCli\Commands');

$statusCode = \Robo\Robo::run(
    $_SERVER['argv'],
    $commandClasses,
    'AcquiaCli',
    '0.0.3'
);
exit($statusCode);
