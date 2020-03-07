<?php

namespace AcquiaCli\Tests;

use Consolidation\Config\ConfigInterface;
use AcquiaCli\CloudApi;
use Robo\Config\Config;
use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Response\EnvironmentResponse;

/**
 * Class CloudApi
 * @package AcquiaCli
 */
class CloudApiTest extends CloudApi
{

    public function __construct(Config $config, Client $client)
    {
        parent::__construct($config);
        $this->extraConfig = $config->get('extraconfig');
        $this->acquia = $config->get('acquia');

        $this->setClient($client);
    }

    public function getInstance()
    {
        return $this->client;
    }

    /**
     * @param string $uuid
     * @param string $environment
     * @return EnvironmentResponse
     * @throws \Exception
     */
    public function getEnvironment($uuid, $environment)
    {

        $env = new \stdClass();
        $env->id = 'bfcc7ad1-f987-41b8-9ea5-f26f0ef3838a';
        $env->label = 'Mock Env';
        $env->name = 'mock';
        $env->domains = 'mock.example.com';
        $env->ssh_url = 'ssh.mock.example.com';
        $env->ips = '1.2.3.4';
        $env->region = 'ap-southeast-2';
        $env->status = 'active';
        $env->type = 'id';
        $env->vcs = 'id';
        $env->configuration = 'id';
        $env->flags = 'id';
        $env->_links = 'id';

        return new EnvironmentResponse($env);
    }
}
