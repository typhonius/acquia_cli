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
    protected $defaultConfigLocation;

    protected $projectConfigLocation;

    /**
     * AcquiaCommand constructor.
     */
    public function __construct()
    {
        $root = Robo::config()->get('config-file.project');
        $default = Robo::config()->get('config-file.default');
        $this->projectConfigLocation = $root . '/acquiacli.yml';
        $this->defaultConfigLocation = $default . '/example.acquiacli.yml';
    }

    /**
     * Creates the AcquiaCli config file with CloudAPI parameters.
     *
     * @command setup
     */
    public function setup()
    {
        if (!file_exists($this->projectConfigLocation)) {
            $this->say('No acquiacli.yml file exists. Creating new file from template now.');
            $mail = $this->ask('What is your Acquia email address?');
            $pass = $this->askHidden('What is your Acquia password or key?');
            $config = [
                'acquia' => [
                    'mail' => $mail,
                    'pass' => $pass,
                ],
                'config-file' => [
                    'default' => $this->defaultConfigLocation,
                    'project' => $this->projectConfigLocation,
                ],
                'extraconfig' => [
                    'timezone' => 'Australia/Sydney',
                ],
            ];

            $yaml = Yaml::dump($config, 3, 2);
            file_put_contents($this->projectConfigLocation, $yaml);
        } else {
            $this->say('acquiacli.yml already exists. Aborting without change.');
        }
    }
}

