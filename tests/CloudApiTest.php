<?php

namespace AcquiaCli\Tests;

use Consolidation\Config\ConfigInterface;
use AcquiaCli\CloudApi;
use Robo\Config\Config;
use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Response\OrganizationResponse;

/**
 * Class CloudApi
 * @package AcquiaCli
 */
class CloudApiTest extends CloudApi
{


    public function __construct(Config $config, Client $client)
    {
        $this->extraConfig = $config->get('extraconfig');
        $this->acquia = $config->get('acquia');

        $this->setClient($client);
        parent::__construct($config);
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

    /**
     * @param string $organization
     * @return OrganizationResponse
     * @throws \Exception
     */
    public function getOrganization($organization)
    {
        $org = new \stdClass();
        $org->id = '4841';
        $org->uuid = 'g47ac10b-58cc-4372-a567-0e02b2c3d472';
        $org->name = 'Sample organization 3';
        $org->owner = new \stdClass();
        $org->owner->uuid = 'u47ac10b-58cc-4372-a567-0e02b2c3d470';
        $org->owner->first_name = 'First';
        $org->owner->last_name = 'Last';
        $org->owner->picture_url = 'https://accounts.acquia.com/path/to/image.png';
        $org->owner->username = 'user.name';
        $org->owner->mail = 'user.name@domain.com';
        $org->subscriptions_total = 4;
        $org->admins_total = 0;
        $org->users_total = 0;
        $org->teams_total = 0;
        $org->roles_total = 0;
        $org->_links = [];

        return new OrganizationResponse($org);
    }
}
