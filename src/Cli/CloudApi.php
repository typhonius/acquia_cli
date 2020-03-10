<?php

namespace AcquiaCli\Cli;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Organizations;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Response\OrganizationResponse;
use Robo\Config\Config;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class CloudApi
 *
 * @package AcquiaCli
 */
class CloudApi
{

    protected $client;

    protected $config;

    public function __construct(Config $config, Client $client)
    {
        $this->config = $config;
        $this->setClient($client);
    }

    public static function createClient(Config $config)
    {

        $acquia = $config->get('acquia');

        if (getenv('ACQUIACLI_KEY') && getenv('ACQUIACLI_SECRET')) {
            $acquia['key'] = getenv('ACQUIACLI_KEY');
            $acquia['secret'] = getenv('ACQUIACLI_SECRET');
        }
        
        $connector = new Connector(
            [
            'key' => $acquia['key'],
            'secret' => $acquia['secret'],
            ]
        );
        /**
         * @var \AcquiaCloudApi\Connector\Client $cloudapi
         */
        $client = Client::factory($connector);

        return $client;
    }

    /**
     * @param  string $name
     * @return mixed
     * @throws \Exception
     */
    public function getApplicationUuid($name)
    {
        $app = new Applications($this->client);
        $applications = $app->getAll();

        foreach ($applications as $application) {
            if ($name === $application->hosting->id) {
                return $application->uuid;
            }
        }
        throw new \Exception('Unable to find UUID for application');
    }

    /**
     * @param  string $uuid
     * @param  string $environment
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

        throw new \Exception('Unable to find environment from environment name');
    }

    /**
     * @param  string $organizationName
     * @return OrganizationResponse
     * @throws \Exception
     */
    public function getOrganization($organizationName)
    {
        $org = new Organizations($this->client);
        $organizations = $org->getAll();

        foreach ($organizations as $organization) {
            if ($organizationName === $organization->name) {
                return $organization;
            }
        }

        throw new \Exception('Unable to find organization from organization name');
    }

    public function getClient()
    {
        if (!$this->client) {
            $client = self::createClient($this->config);
            $this->setClient($client);
        }
        return $this->client;
    }

    public function setClient($client)
    {
        $this->client = $client;
    }
}
