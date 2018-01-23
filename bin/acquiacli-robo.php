<?php

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Robo\Config\Config;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use AcquiaCli\AcquiaCli;

if (strpos(basename(__FILE__), 'phar')) {
    $root = __DIR__;
    require_once 'phar://acquiacli.phar/vendor/autoload.php';
} else {
    if (file_exists(dirname(__DIR__).'/vendor/autoload.php')) {
        $root = dirname(__DIR__);
        require_once dirname(__DIR__) . '/vendor/autoload.php';
    } elseif (file_exists(dirname(__DIR__) . '/../../autoload.php')) {
        $root = dirname(__DIR__) . '/../../..';
        require_once dirname(__DIR__) . '/../../autoload.php';
    } else {
        $root = __DIR__;
        require_once 'phar://acquiacli.phar/vendor/autoload.php';
    }
}

$config = new Config();
$loader = new YamlConfigLoader();
$processor = new ConfigProcessor();

$globalConfig = getenv('HOME') . '/.acquiacli/acquiacli.yml';
$projectConfig = $root . '/acquiacli.yml';
$paths = [
    dirname(__DIR__) . '/default.acquiacli.yml',
    $globalConfig,
    $projectConfig,
];

foreach ($paths as $path) {
    $processor->extend($loader->load($path));
}

$config->import($processor->export());
$config->set('config.project', $projectConfig);
$config->set('config.global', $globalConfig);

$input = new ArgvInput($argv);
$output = new ConsoleOutput();
$app = new AcquiaCli($config, $input, $output);
$statusCode = $app->run($input, $output);
exit($statusCode);
