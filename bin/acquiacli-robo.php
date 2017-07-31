<?php

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Robo\Robo;
use AcquiaCli\AcquiaCli;

if (strpos(basename(__FILE__), 'phar')) {
    $root = __DIR__;
    require_once 'phar://acquiacli.phar/vendor/autoload.php';
} else {
    if (file_exists(dirname(__DIR__).'/vendor/autoload.php')) {
        $root = dirname(__DIR__);
        require_once dirname(__DIR__) . '/vendor/autoload.php';
    } elseif (file_exists(__DIR__ . '/../../../autoload.php')) {
        $root = __DIR__ . '/../../../';
        require_once __DIR__ . '/../../../autoload.php';
    } else {
        $root = __DIR__;
        require_once 'phar://acquiacli.phar/vendor/autoload.php';
    }
}

$input = new ArgvInput($argv);
$output = new ConsoleOutput();
$config = Robo::createConfiguration(['acquiacli.yml']);
$config->set('config-file.project', $root);
$config->set('config-file.default', dirname(__DIR__));
$app = new AcquiaCli($config, $input, $output);
$status_code = $app->run($input, $output);
exit($status_code);
