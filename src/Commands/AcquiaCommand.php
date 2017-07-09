<?php

namespace AcquiaCli\Commands;

use Robo\Tasks;
use Robo\Robo;
use Acquia\Cloud\Api\CloudApiClient;

abstract class AcquiaCommand extends Tasks
{
    /** @var CloudApiClient $cloudapi */
    protected $cloudapi;

    /** Additional configuration */
    protected $extraConfig;

    /**
     * This hook will fire for all commands extending this class.
     *
     * @hook init
     */
    public function construct()
    {
        $extraConfig = Robo::Config()->get('extraconfig');
        $this->extraConfig = $extraConfig;

        $acquia = Robo::Config()->get('acquia');
        $cloudapi = CloudApiClient::factory(array(
            'username' => $acquia['mail'],
            'password' => $acquia['pass'],
        ));

        $this->cloudapi = $cloudapi;
    }
}

