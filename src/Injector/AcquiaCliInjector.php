<?php

namespace AcquiaCli\Injector;

use Consolidation\AnnotatedCommand\ParameterInjector;
use Consolidation\AnnotatedCommand\CommandData;
use AcquiaCli\CloudApi;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Databases;
use AcquiaCloudApi\Endpoints\Servers;
use AcquiaCloudApi\Endpoints\Domains;
use AcquiaCloudApi\Endpoints\Code;
use AcquiaCloudApi\Endpoints\DatabaseBackups;
use AcquiaCloudApi\Endpoints\Crons;

class AcquiaCliInjector implements ParameterInjector
{

    protected $config;
    protected $cloudapi;
    protected $client;

    public function __construct()
    {
        $this->config = \Robo\Robo::config();
        $this->cloudapi = \Robo\Robo::service('cloudApi');
        $this->client = \Robo\Robo::service('client');
    }

    public function get(CommandData $commandData, $interfaceName)
    {
        switch ($interfaceName) {
            case 'AcquiaCli\CloudApi':
                return $this->cloudapi;
            case 'AcquiaCli\Config':
                return $this->config;
            case 'AcquiaCloudApi\Endpoints\Applications':
                return new Applications($this->client);
            case 'AcquiaCloudApi\Endpoints\Environments':
                return new Environments($this->client);
            case 'AcquiaCloudApi\Endpoints\Databases':
                return new Databases($this->client);
            case 'AcquiaCloudApi\Endpoints\Servers':
                return new Servers($this->client);
            case 'AcquiaCloudApi\Endpoints\Domains':
                return new Domains($this->client);
            case 'AcquiaCloudApi\Endpoints\Code':
                return new Code($this->client);
            case 'AcquiaCloudApi\Endpoints\DatabaseBackups':
                return new DatabaseBackups($this->client);
            case 'AcquiaCloudApi\Endpoints\Crons':
                return new Crons($this->client);
        }

        return null;
    }
}
