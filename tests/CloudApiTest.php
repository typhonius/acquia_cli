<?php

namespace AcquiaCli\Tests;

use Consolidation\Config\ConfigInterface;

/**
 * Class CloudApi
 * @package AcquiaCli
 */
final class CloudApiTest
{

    private $client;
    private $extraConfig;

    public function __construct(ConfigInterface $config, $client)
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
