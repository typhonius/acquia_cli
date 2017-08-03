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
    protected $projectConfigLocation;

    protected $globalConfigLocation;

    /**
     * AcquiaCommand constructor.
     */
    public function __construct()
    {
        $project = Robo::config()->get('config.project');
        $global = Robo::config()->get('config.global');
        $this->projectConfigLocation = $project;
        $this->globalConfigLocation = $global;
    }

    /**
     * Creates the AcquiaCli config file with CloudAPI parameters.
     *
     * @command setup
     */
    public function setup()
    {
        $globalConfig = $this->globalConfigLocation;
        $projectConfig = $this->projectConfigLocation;
        if (file_exists($globalConfig)) {
            $this->say("Global configuration file found at ${globalConfig}");
            $this->say('Delete the acquiacli.yml file in your global config directory and run the setup command again to regenerate it.');
        } elseif ($this->confirm('Would you like to add a global config file?')) {
            $yaml = $this->createConfigYaml();
            if (!is_dir(dirname($globalConfig))) {
                mkdir(dirname($globalConfig));
            }
            file_put_contents($globalConfig, $yaml);
        }

        if (file_exists($projectConfig)) {
            $this->say("Local configuration file found at ${projectConfig}");
            $this->say('Delete the acquiacli.yml file in your project root and run the setup command again to regenerate it.');
        } elseif ($this->confirm('Would you like to add a local config file? This will override any global config file.')) {
            $yaml = $this->createConfigYaml();
            file_put_contents($projectConfig, $yaml);
        }
    }

    /**
     * @return string
     */
    private function createConfigYaml()
    {
        $mail = $this->ask('What is your Acquia email address?');
        $pass = $this->askHidden('What is your CloudAPI key or Acquia password?');

        $config = [
            'acquia' => [
                'mail' => $mail,
                'pass' => $pass,
            ],
            'extraconfig' => [
                'timezone' => 'Australia/Sydney',
                'format' => 'Y-m-d H:i:s',
            ],
        ];

        return Yaml::dump($config, 3, 2);
    }
}

