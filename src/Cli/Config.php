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

        $homeDir = getenv('HOME');
        $defaultConfig = join(DIRECTORY_SEPARATOR, [dirname(dirname(__DIR__)), 'default.acquiacli.yml']);
        $globalConfig = join(DIRECTORY_SEPARATOR, [$homeDir, '.acquiacli', 'acquiacli.yml']);
        $projectConfig = join(DIRECTORY_SEPARATOR, [$root, 'acquiacli.yml']);

        $processor->extend($loader->load($defaultConfig));
        $processor->extend($loader->load($globalConfig));
        $processor->extend($loader->load($projectConfig));

        $this->import($processor->export());
        $this->set('config.project', $projectConfig);
        $this->set('config.global', $globalConfig);
    }
}
