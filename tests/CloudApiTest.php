<?php

namespace AcquiaCli\Tests;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;

/**
 * Class CloudApi
 * @package AcquiaCli
 */
final class CloudApiTest
{

    private $client;
    private $applications;

    public function __construct($config, $client)
    {
        $this->extraConfig = $config->get('extraconfig');
        $this->setCloudApi($client);
    }

    public function getCloudApi()
    {
        return $this->client;
    }

    public function setCloudApi($client)
    {
        $this->client = $client;
    }

    public function getExtraConfig()
    {
        return $this->extraConfig;
    }
}
