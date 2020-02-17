<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Endpoints\Environments;

/**
 * Class SshCommand
 * @package AcquiaCli\Commands
 */
class SshCommand extends AcquiaCommand
{

    public $environmentsAdapter;

    public function __construct()
    {
        parent::__construct();

        $this->environmentsAdapter = new Environments($this->cloudapi);
    }

    /**
     * Shows SSH connection strings for specified environments.
     *
     * @param string      $uuid
     * @param string|null $env
     *
     * @command ssh:info
     */
    public function sshInfo($uuid, $env = null)
    {

        if (null !== $env) {
            $this->cloudapi->addQuery('filter', "name=${env}");
        }

        $environments = $this->environmentsAdapter->getAll($uuid);

        $this->cloudapi->clearQuery();

        foreach ($environments as $e) {
            /** @var $e EnvironmentResponse */
            $this->say($e->name . ': ssh ' . $e->sshUrl);
        }
    }
}
