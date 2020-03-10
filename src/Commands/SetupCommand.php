<?php

namespace AcquiaCli\Commands;

use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\Yaml\Yaml;

/**
 * Class SetupCommand
 *
 * @package AcquiaCli\Commands
 */
class SetupCommand extends Tasks
{

    protected $configFiles;

    /**
     * AcquiaCommand constructor.
     */
    public function __construct()
    {
        $this->configFiles = [
            'global' => Robo::config()->get('config.global'),
            'project' => Robo::config()->get('config.project'),
        ];
    }

    /**
     * Performs a check of the config files and provides a view of the parameters provided. Allows the user to create
     * new config files with correct parameters.
     *
     * @command setup
     */
    public function setup()
    {
        foreach ($this->configFiles as $type => $location) {
            $this->say(sprintf('Checking %s configuration at %s', $type, $location));
            if (file_exists($location)) {
                $this->yell(sprintf('%s configuration file found', $type));
                if (!is_readable($location) && !@chmod($location, 0644)) {
                    $this->yell(sprintf('%s configuration is not readable', $type), 40, 'red');
                    continue;
                }
                if ($this->confirm('Would you like to view the contents of this file?')) {
                    if ($contents = file_get_contents($location)) {
                        $this->say($contents);
                    }
                }
                if ($this->confirm("Would you like to delete and regenerate the acquiacli.yml file at ${location}?")) {
                    $this->createConfigYaml($location);
                }
            } elseif ($this->confirm(sprintf('No file found. Would you like to add a file at %s?', $location))) {
                $this->createConfigYaml($location);
            }
        }
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
        if (file_put_contents($location, $yaml)) {
            $this->say(sprintf('Configuration file written to %s', $location));
        } else {
            $this->say('Unable to write configuration file.');
        }
    }
}
