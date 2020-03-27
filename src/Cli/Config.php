<?php

namespace AcquiaCli\Cli;

use Robo\Config\Config as RoboConfig;
use Robo\Config\GlobalOptionDefaultValuesInterface;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;

/**
 * Class Config
 *
 * @package AcquiaCli
 */
class Config extends RoboConfig implements GlobalOptionDefaultValuesInterface
{
    public function __construct($root)
    {
        parent::__construct();

        $loader = new YamlConfigLoader();
        $processor = new ConfigProcessor();

        $home = self::getHome();

        $defaultConfig = join(DIRECTORY_SEPARATOR, [dirname(dirname(__DIR__)), 'default.acquiacli.yml']);
        $globalConfig = join(DIRECTORY_SEPARATOR, [$home, '.acquiacli', 'acquiacli.yml']);
        $projectConfig = join(DIRECTORY_SEPARATOR, [$root, 'acquiacli.yml']);

        $processor->extend($loader->load($defaultConfig));
        $processor->extend($loader->load($globalConfig));
        $processor->extend($loader->load($projectConfig));

        $this->import($processor->export());
        $this->set('config.project', $projectConfig);
        $this->set('config.global', $globalConfig);
    }

    public static function isWindows()
    {
        return stripos(PHP_OS, 'WIN') === 0;
    }

    public static function getHome()
    {
        $home = getenv('HOME');
        if (self::isWindows()) {
            $home = getenv('USERPROFILE');
        }

        if (!$home) {
            throw new \Exception('Home directory not found.');
        }

        return $home;
    }
}
