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
     * Runs a deployment of a branch/tag and config/db update to the production environment.
     *
     * @param string $uuid
     * @param string $branch
     *
     * @command prod:deploy
     */
    public function acquiaDeployProd($uuid, $branch)
    {
        $this->yell('WARNING: DEPLOYING TO PROD');
        if ($this->confirm('Are you sure you want to deploy to prod?')) {
            $environment = $this->getEnvironmentFromEnvironmentName($uuid, 'prod');
            $this->acquiaDeployEnv($uuid, $environment, $branch);
        }
    }

    /**
     * Runs a deployment of a branch/tag and config/db update to a non-production environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $branch
     * @throws \Exception
     *
     * @command preprod:deploy
     */
    public function acquiaDeployPreProd($uuid, $environment, $branch)
    {
        if ($environment->name == 'prod') {
            throw new \Exception('Use the prod:deploy command for the production environment.');
        }

        $this->acquiaDeployEnv($uuid, $environment, $branch);
    }

    /**
     * Updates configuration and db in production.
     *
     * @param string $uuid
     *
     * @command prod:config-update
     */
    public function acquiaConfigUpdateProd($uuid)
    {
        $this->yell('WARNING: UPDATING CONFIG ON PROD');
        if ($this->confirm('Are you sure you want to update prod config? This will overwrite your configuration.')) {
            $this->acquiaConfigUpdate($site, 'prod');
        if ($this->confirm('Are you sure you want to update prod config? This will overwrite your prod configuration.')) {
            $environment = $this->getEnvironmentFromEnvironmentName($uuid, 'prod');
            $this->acquiaConfigUpdate($environment);
        }
    }

    /**
     * Updates configuration and db in a non-production environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @throws \Exception
     *
     * @command preprod:config-update
     */
    public function acquiaConfigUpdatePreProd($uuid, $environment)
    {
        if ($environment->name == 'prod') {
            throw new \Exception('Use the prod:config-update command for the production environment.');
        }

        $this->acquiaConfigUpdate($environment);
    }

    /**
     * Prepares a non-production environment for deployment by copying the database and files from another environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     * @throws \Exception
     *
     * @command preprod:prepare
     */
    public function acquiaPreparePreProd($uuid, $environmentFrom, $environmentTo)
    {
        if ($environmentTo->name == 'prod') {
            throw new \Exception('Use the db:backup and files:copy commands for the production environment.');
        }

        $this->backupAndMoveDbs($uuid, $environmentFrom, $environmentTo);
        $this->backupFiles($uuid, $environmentFrom, $environmentTo);
    }

    /**
     * Clears varnish cache for all domains in specific a specific pre-production environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @throws \Exception
     *
     * @command preprod:purgevarnish
     */
    public function acquiaPurgeVarnish($uuid, $environment)
    {
        if ($environment->name == 'prod') {
            throw new \Exception('Use the prod:purgevarnish command for the production environment.');
        }

        $this->acquiaPurgeVarnishForEnvironment($uuid, $environment);
    }

    /**
     * Clears varnish cache for all domains the production environment.
     *
     * @param string $uuid
     *
     * @command prod:purgevarnish
     */
    public function acquiaPurgeVarnishProd($uuid)
    {
        $this->yell('WARNING: CLEARNING PROD VARNISH CACHE CAN RESULT IN REDUCTION IN PERFORMANCE');
        if ($this->confirm('Are you sure you want to clear prod varnish cache?')) {
            $environment = $this->getEnvironmentFromEnvironmentName($uuid, 'prod');
            $this->acquiaPurgeVarnishForEnvironment($uuid, $environment);
        }
    }
}
