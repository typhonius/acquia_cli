<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Response\EnvironmentsResponse;
use Symfony\Component\Console\Helper\Table;

/**
 * Class SshCommand
 * @package AcquiaCli\Commands
 */
class SshCommand extends AcquiaCommand
{

    /**
     * Shows SSH connection strings for specified environments.
     *
     * @param string      $uuid
     * @param string|null $env
     *
     * @command ssh:info
     */
    public function acquiaSshInfo($uuid, $env = null)
    {

        if (null !== $env) {
            $this->cloudapi->addQuery('filter', "name=${env}");
        }

        $environments = $this->cloudapi->environments($uuid);

        $this->cloudapi->clearQuery();

        foreach ($environments as $e) {
            $this->renderSshInfo($e);
        }
    }

    private function renderSshInfo(EnvironmentResponse $environment)
    {
        $environmentName = $environment->name;
        $ssh = $environment->sshUrl;
        $this->say("${environmentName}: ssh ${ssh}");
    }
}
