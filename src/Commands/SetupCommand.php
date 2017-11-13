<?php

namespace AcquiaCli\Commands;

use Robo\Robo;
use Robo\Tasks;
use Symfony\Component\Yaml\Yaml;

/**
 * Class SetupCommand
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
     *  Performs a check of the config files and provides a view of the parameters provided. Allows the user to create
     * new config files with correct parameters.
     *
     * @command setup
     */
    public function setup()
    {
        foreach ($this->configFiles as $type => $location) {
            $this->say("Checking ${type} configuration at ${location}");
            if (file_exists($location)) {
                $this->yell("${type} configuration file found");
                if (!is_readable($location) && !@chmod($location, 0644)) {
                    $this->yell("${type} configuration is not readable", 40, 'red');
                    continue;
                }
                if ($this->confirm('Would you like to view the contents of this file?')) {
                    $this->say(file_get_contents($location));
                }
                if ($this->confirm("Would you like to delete and regenerate the acquiacli.yml file at ${location}?")) {
                    $this->createConfigYaml($location);
                }
            } elseif ($this->confirm("No file found. Would you like to add a file at ${location}?")) {
                $this->createConfigYaml($location);
            }
        }
    }

    /**
     *
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
                'timeout' => 120,
            ],
        ];


        if ($this->confirm('Do you want to enter Cloudflare information?')) {
            $cfmail = $this->ask('What is your Cloudflare email address?');
            $cfkey = $this->askHidden('What is your Cloudflare API key?');

            $config = $config + [
                'cloudflare' => [
                    'mail' => $cfmail,
                    'key' => $cfkey,
                ],
            ];
        }

        $yaml = Yaml::dump($config, 3, 2);

        if (!is_dir(dirname($location))) {
            mkdir(dirname($location));
        }
        file_put_contents($location, $yaml);
    }
}
