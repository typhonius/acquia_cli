<?php

namespace AcquiaCli\Cli;

use Robo\Config\Config as RoboConfig;
use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use Robo\Config\GlobalOptionDefaultValuesInterface;

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

        $environment = [];
        if (getenv('ACQUIACLI_KEY')) {
            $environment['acquia']['key'] = getenv('ACQUIACLI_KEY');
        }
        if (getenv('ACQUIACLI_SECRET')) {
            $environment['acquia']['secret'] = getenv('ACQUIACLI_SECRET');
        }
        if (getenv('ACQUIACLI_TIMEZONE')) {
            $environment['extraconfig']['timezone'] = getenv('ACQUIACLI_TIMEZONE');
        }
        if (getenv('ACQUIACLI_FORMAT')) {
            $environment['extraconfig']['format'] = getenv('ACQUIACLI_FORMAT');
        }
        if (getenv('ACQUIACLI_TASKWAIT')) {
            $environment['extraconfig']['taskwait'] = getenv('ACQUIACLI_TASKWAIT');
        }
        if (getenv('ACQUIACLI_TIMEOUT')) {
            $environment['extraconfig']['timeout'] = getenv('ACQUIACLI_TIMEOUT');
        }

        $processor->extend($loader->load($defaultConfig));
        $processor->extend($loader->load($globalConfig));
        $processor->extend($loader->load($projectConfig));
        $processor->add($environment);

        $this->import($processor->export());
        $this->set('config.default', $defaultConfig);
        $this->set('config.global', $globalConfig);
        $this->set('config.project', $projectConfig);
        $this->set('config.environment', $environment);
    }
}
