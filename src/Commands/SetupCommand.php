<?php

namespace AcquiaCli\Commands;

use Robo\Tasks;
use AcquiaCli\Cli\Config;
use Symfony\Component\Yaml\Yaml;

/**
 * Class SetupCommand
 *
 * @package AcquiaCli\Commands
 */
class SetupCommand extends Tasks
{

    /**
     * Performs a check of the config files and provides a view of the parameters provided. Allows the user to create
     * new config files with correct parameters.
     *
     * @command setup
     */
    public function setup(Config $config)
    {
        $configFiles = ['global' => $config->get('config.global')];

        // Do not include project configuration if this is running in a Phar.
        if (!\Phar::running()) {
            $configFiles['project'] = $config->get('config.project');
        }

        foreach ($configFiles as $type => $location) {
            $this->yell(sprintf('%s configuration (%s)', ucfirst($type), $location));

            if (
                file_exists($location) && $this->confirm(
                    sprintf('Would you like to regenerate the %s configuration file', $type)
                )
            ) {
                $this->createConfigYaml($location);
            } elseif (
                $this->confirm(
                    sprintf('%s configuration file not found. Would you like to add one?', ucfirst($type))
                )
            ) {
                $this->createConfigYaml($location);
            }
        }
    }

    /**
     * Allows users to view the configuration that they have on their system.
     *
     * This provides a view of default, global, project, and environment variable based configuration.
     *
     * @command setup:config:view
     */
    public function configView(Config $config)
    {
        $configFiles = [
            'default' => $config->get('config.default'),
            'global' => $config->get('config.global'),
            'environment' => $config->get('config.environment')
        ];

        // Do not include project configuration if this is running in a Phar.
        if (!\Phar::running()) {
            $configFiles['project'] = $config->get('config.project');
        }

        foreach ($configFiles as $type => $data) {
            $contents = '';
            if (is_array($data)) {
                if (empty($data)) {
                    continue;
                }
                $contents = Yaml::dump($data);
            } elseif (file_exists($data) && is_readable($data)) {
                $contents = file_get_contents($data);
            } else {
                continue;
            }
            $this->yell(sprintf('%s configuration', ucfirst($type)));
            $this->writeln($contents);
        }

        $this->yell('Running configuration');
        $running = [
            'acquia' => $config->get('acquia'),
            'extraconfig' => $config->get('extraconfig')
        ];

        $this->writeln(Yaml::dump($running));
    }

    /**
     * Function to create configuration files for this library.
     */
    private function createConfigYaml($location)
    {
        $key = $this->ask('What is your Acquia key?');
        $secret = $this->askHidden('What is your Acquia secret?');

        $config = [
            'acquia' => [
                'key' => $key,
                'secret' => $secret,
            ],
            'extraconfig' => [
                'timezone' => 'Australia/Sydney',
                'format' => 'Y-m-d H:i:s',
                'taskwait' => 5,
                'timeout' => 300,
                'configsyncdir' => 'sync',
            ],
        ];

        $yaml = Yaml::dump($config, 3, 2);

        if (!is_dir(dirname($location))) {
            mkdir(dirname($location), 700);
        }

        if (!is_writable($location) && !@chmod($location, 0644)) {
            $this->yell(sprintf('%s is not writeable', ucfirst($location)), 40, 'red');
        } elseif (file_put_contents($location, $yaml)) {
            $this->say(sprintf('Configuration file written to %s', $location));
        } else {
            $this->say('Unable to write configuration file.');
        }
    }
}
