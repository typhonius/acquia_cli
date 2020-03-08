<?php

namespace AcquiaCli;

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
 * @package AcquiaCli
 */
class CloudApi
{

    protected $client;

    protected $extraConfig;

    protected $acquia;

    public function __construct(Config $config, Client $client = null)
    {
        $this->extraConfig = $config->get('extraconfig');
        $this->acquia = $config->get('acquia');
        $this->client = $client;
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
     * @param string $name
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

    /**
     * @param string $organizationName
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

        throw new \Exception('Unable to find ID for organization');
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
