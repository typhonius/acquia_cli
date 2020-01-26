<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;

/**
 * Class DeployCommand
 * @package AcquiaCli\Commands
 */
class DeployCommand extends AcquiaCommand
{
    // /**
    //  * Runs a deployment of a branch/tag and config/db update to the production environment.
    //  *
    //  * @param string $uuid
    //  * @param string $branch
    //  *
    //  * @command prod:deploy
    //  */
    // public function acquiaDeployProd($uuid, $branch)
    // {
    //     $this->yell('WARNING: DEPLOYING TO PROD');
    //     if ($this->confirm('Are you sure you want to deploy to prod?')) {
    //         $environment = $this->getEnvironmentFromEnvironmentName($uuid, 'prod');
    //         $this->acquiaDeployEnv($uuid, $environment, $branch);
    //     }
    // }

    // /**
    //  * Runs a deployment of a branch/tag and config/db update to a non-production environment.
    //  *
    //  * @param string              $uuid
    //  * @param EnvironmentResponse $environment
    //  * @param string              $branch
    //  * @throws \Exception
    //  *
    //  * @command preprod:deploy
    //  */
    // public function acquiaDeployPreProd($uuid, $environment, $branch)
    // {
    //     if ($environment->name == 'prod') {
    //         throw new \Exception('Use the prod:deploy command for the production environment.');
    //     }

    //     $this->acquiaDeployEnv($uuid, $environment, $branch);
    // }

    // /**
    //  * Runs a deployment of code from an environment to a non-production environment.
    //  *
    //  * @param string              $uuid
    //  * @param EnvironmentResponse $environmentFrom
    //  * @param EnvironmentResponse $environmentTo
    //  * @throws \Exception
    //  *
    //  * @command preprod:deployfromenv
    //  */
    // public function acquiaDeployPreProdFromEnv($uuid, $environmentFrom, $environmentTo)
    // {
    //     if ($environmentTo->name == 'prod') {
    //         throw new \Exception('Use the prod:deployfromenv command for the production environment.');
    //     }

    //     $this->acquiaDeployEnvToEnv($uuid, $environmentFrom, $environmentTo);
    // }

    // /**
    //  * Prepares a non-production environment for deployment
    //  * by copying the database and files from another environment.
    //  *
    //  * @param string              $uuid
    //  * @param EnvironmentResponse $environmentFrom
    //  * @param EnvironmentResponse $environmentTo
    //  * @throws \Exception
    //  *
    //  * @command preprod:prepare
    //  */
    // public function acquiaPreparePreProd($uuid, $environmentFrom, $environmentTo)
    // {
    //     if ($environmentTo->name == 'prod') {
    //         throw new \Exception('Use the db:backup and files:copy commands for the production environment.');
    //     }

    //     $this->backupAndMoveDbs($uuid, $environmentFrom, $environmentTo);
    //     $this->copyFiles($uuid, $environmentFrom, $environmentTo);
    // }
}
