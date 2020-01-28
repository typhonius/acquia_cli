<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;

/**
 * Class DeployCommand
 * @package AcquiaCli\Commands
 */
class DeployCommand extends AcquiaCommand
{
    /**
     * Prepares a non-production environment for a deployment by pulling back
     * the production database and files to the non-prod environment.
     *
     * @param string                   $uuid
     * @param EnvironmentResponse      $environmentTo
     * @param EnvironmentResponse|null $environmentFrom
     *
     * @command deploy:prepare
     */
    public function deployPrepare($uuid, $environmentTo, $environmentFrom = null)
    {
        if ($environmentTo->name === 'prod') {
            throw new \Exception('Cannot use deploy:prepare on the production environment');
        }

        if ($environmentFrom === null) {
            $environmentFrom = $this->getEnvironmentFromEnvironmentName($uuid, 'prod');
        }

        $this->backupAndMoveDbs($uuid, $environmentFrom, $environmentTo);
        $this->copyFiles($uuid, $environmentFrom, $environmentTo);
    }
}
