<?php

namespace AcquiaCli;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;

/**
 * Class CloudApi
 * @package AcquiaCli
 */
final class CloudApi
{

    private $cloudapi;

    private $extraConfig;

    public function __construct($config)
    {

        $this->extraConfig = $config->get('extraconfig');
        $acquia = $config->get('acquia');

        if (getenv('ACQUIACLI_KEY') && getenv('ACQUIACLI_SECRET')) {
            $acquia['key'] = getenv('ACQUIACLI_KEY');
            $acquia['secret'] = getenv('ACQUIACLI_SECRET');
        }

        $connector = new Connector([
            'key' => $acquia['key'],
            'secret' => $acquia['secret'],
        ]);
        /** @var \AcquiaCloudApi\Connector\Client $cloudapi */
        $cloudapi = Client::factory($connector);

        $this->setCloudApi($cloudapi);
    }

    public function getCloudApi()
    {
        return $this->cloudapi;
    }

    public function setCloudApi($cloudapi)
    {
        $this->cloudapi = $cloudapi;
    }

    public function getExtraConfig()
    {
        return $this->extraConfig;
    }
}
