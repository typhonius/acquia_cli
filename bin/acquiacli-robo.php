<?php

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Robo\Robo;
use AcquiaCli\AcquiaCli;

if (strpos(basename(__FILE__), 'phar')) {
    require_once 'phar://acquiacli.phar/vendor/autoload.php';
} else {
    if (file_exists(dirname(__DIR__).'/vendor/autoload.php')) {
        require_once dirname(__DIR__).'/vendor/autoload.php';
    } elseif (file_exists(__DIR__.'/../../../autoload.php')) {
        require_once __DIR__ . '/../../../autoload.php';
    } else {
        require_once 'phar://acquiacli.phar/vendor/autoload.php';
    }
}

$input = new ArgvInput($argv);
$output = new ConsoleOutput();
$config = Robo::createConfiguration(['acquiacli.yml']);
$app = new AcquiaCli($config, $input, $output);
$status_code = $app->run($input, $output);
exit($status_code);
