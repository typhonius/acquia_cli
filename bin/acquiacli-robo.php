<?php

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use AcquiaCli\Cli\Config;
use AcquiaCli\Cli\AcquiaCli;
use AcquiaCli\Cli\CloudApi;

$pharPath = \Phar::running(true);
if ($pharPath) {
    $root = __DIR__;
    $autoloaderPath = "$pharPath/vendor/autoload.php";
} else {
    if (file_exists(dirname(__DIR__).'/vendor/autoload.php')) {
        $root = dirname(__DIR__);
        $autoloaderPath = dirname(__DIR__).'/vendor/autoload.php';
    } elseif (file_exists(dirname(__DIR__).'/../../autoload.php')) {
        $root = dirname(__DIR__) . '/../../..';
        $autoloaderPath = dirname(__DIR__) . '/../../autoload.php';
    } else {
        die("Could not find autoloader. Run 'composer install'.");
    }
}
$classLoader = require $autoloaderPath;

$config = new Config($root);

// Instantiate CloudApi client
$client = CloudApi::createClient($config);

// Set up input and output parameters
$argv = $_SERVER['argv'];
$input = new ArgvInput($argv);
$output = new ConsoleOutput();

// Create and run AcquiaCli instance
$app = new AcquiaCli($config, $client, $input, $output);
$statusCode = $app->run($input, $output);
exit($statusCode);
