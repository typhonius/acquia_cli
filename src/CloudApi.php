<?php

namespace AcquiaCli;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Response\EnvironmentResponse;
use Robo\Config\Config;

/**
 * Class CloudApi
 * @package AcquiaCli
 */
class CloudApi
{

    protected $client;

    protected $extraConfig;

    protected $acquia;

    public function __construct(Config $config)
    {
        $this->extraConfig = $config->get('extraconfig');
        $this->acquia = $config->get('acquia');
    }

    public function createClient()
    {

        if (getenv('ACQUIACLI_KEY') && getenv('ACQUIACLI_SECRET')) {
            $this->acquia['key'] = getenv('ACQUIACLI_KEY');
            $this->acquia['secret'] = getenv('ACQUIACLI_SECRET');
        }
        
        $connector = new Connector([
            'key' => $this->acquia['key'],
            'secret' => $this->acquia['secret'],
        ]);
        /** @var \AcquiaCloudApi\Connector\Client $cloudapi */
        $client = Client::factory($connector);

        $this->setClient($client);

        return $client;
    }

    /**
     * @param string $uuid
     * @param string $environment
     * @return EnvironmentResponse
     * @throws \Exception
     */
    public function getEnvironment($uuid, $environment)
    {
        $environmentsAdapter = new Environments($this->client);
        $environments = $environmentsAdapter->getAll($uuid);

        foreach ($environments as $e) {
            if ($environment === $e->name) {
                return $e;
            }
        }

        throw new \Exception('Unable to find ID for environment');
    }

    public function getClient()
    {
        if (!$this->client) {
            $this->createClient();
        }
        return $this->client;
    }

    protected function setClient($client)
    {
        $this->client = $client;
    }

    public function getExtraConfig()
    {
        return $this->extraConfig;
    }
}
