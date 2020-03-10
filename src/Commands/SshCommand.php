<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Environments;

/**
 * Class SshCommand
 *
 * @package AcquiaCli\Commands
 */
class SshCommand extends EnvironmentsCommand
{

    /**
     * Shows SSH connection strings for specified environments.
     *
     * @param string      $uuid
     * @param string|null $env
     *
     * @command ssh:info
     */
    public function sshInfo(Client $client, Environments $environmentsAdapter, $uuid, $env = null)
    {

        if (null !== $env) {
            $client->addQuery('filter', "name=${env}");
        }

        $environments = $environmentsAdapter->getAll($uuid);

        $client->clearQuery();

        foreach ($environments as $e) {
            /**
             * @var $e EnvironmentResponse
             */
            $this->say($e->name . ': ssh ' . $e->sshUrl);
        }
    }
}
