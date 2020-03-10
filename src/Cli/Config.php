<?php

namespace AcquiaCli\Cli;

use Robo\Config\Config as RoboConfig;
use Robo\Config\GlobalOptionDefaultValuesInterface;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;

/**
 * Class Config
 * @package AcquiaCli
 */
class Config extends RoboConfig implements GlobalOptionDefaultValuesInterface
{
    public function __construct($root)
    {
        parent::__construct();

        $loader = new YamlConfigLoader();
        $processor = new ConfigProcessor();

        $globalConfig = getenv('HOME') . '/.acquiacli/acquiacli.yml';
        $projectConfig = $root . '/acquiacli.yml';

        $processor->extend($loader->load(dirname(dirname(__DIR__)) . '/default.acquiacli.yml'));
        $processor->extend($loader->load($globalConfig));
        $processor->extend($loader->load($projectConfig));

        $this->import($processor->export());
        $this->set('config.project', $projectConfig);
        $this->set('config.global', $globalConfig);
    }
}
