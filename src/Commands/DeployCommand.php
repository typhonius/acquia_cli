<?php

namespace AcquiaCli\Commands;

/**
 * Class DeployCommand
 * @package AcquiaCli\Commands
 */
class DeployCommand extends AcquiaCommand
{
    /**
     * Runs a deployment of a branch/tag and config/db update to the production environment.
     *
     * @param string $site
     * @param string $branch
     *
     * @command prod:deploy
     */
    public function acquiaDeployProd($site, $branch)
    {
        $this->yell('WARNING: DEPLOYING TO PROD');
        if ($this->confirm('Are you sure you want to deploy to prod?')) {
            $this->acquiaDeployEnv($site, 'prod', $branch);
        }
    }

    /**
     * Runs a deployment of a branch/tag and config/db update to a non-production environment.
     *
     * @param string $site
     * @param string $environment
     * @param string $branch
     * @throws \Exception
     *
     * @command preprod:deploy
     */
    public function acquiaDeployPreProd($site, $environment, $branch)
    {
        if ($environment == 'prod') {
            throw new \Exception('Use the prod:acquia:deploy command for the production environment.');
        }

        $this->acquiaDeployEnv($site, $environment, $branch);
    }

    /**
     * Updates configuration and db in production.
     *
     * @param string $site
     *
     * @command prod:config-update
     */
    public function acquiaConfigUpdateProd($site)
    {
        $this->yell('WARNING: UPDATING CONFIG ON PROD');
        if ($this->confirm('Are you sure you want to update prod config? This will overwrite your prod configuration.')) {
            $this->acquiaConfigUpdate($site, 'prod');
        }
    }

    /**
     * Updates configuration and db in a non-production environment.
     *
     * @param string $site
     * @param string $environment
     * @throws \Exception
     *
     * @command preprod:config-update
     */
    public function acquiaConfigUpdatePreProd($site, $environment)
    {
        if ($environment == 'prod') {
            throw new \Exception('Use the prod:acquia:prepare command for the production environment.');
        }

        $this->acquiaConfigUpdate($site, $environment);
    }

    /**
     * Prepares a non-production environment for a deployment by copying the database and files from another environment.
     *
     * @param string $site
     * @param string $environmentFrom
     * @param string $environmentTo
     * @throws \Exception
     *
     * @command preprod:prepare
     */
    public function acquiaPreparePreProd($site, $environmentFrom, $environmentTo)
    {
        if ($environmentTo == 'prod') {
            throw new \Exception('Use the acquia:prepare:prod command for the production environment.');
        }

        $this->backupAndMoveDbs($site, $environmentFrom, $environmentTo);
        $this->backupFiles($site, $environmentFrom, $environmentTo);
    }

    /**
     * Clears varnish cache for all domains in specific a specific pre-production environment.
     *
     * @param string $site
     * @param string $environment
     * @throws \Exception
     *
     * @command preprod:purgevarnish
     */
    public function acquiaPurgeVarnish($site, $environment)
    {
        if ($environment == 'prod') {
            throw new \Exception('Use the prod:acquia:purgevarnish command for the production environment.');
        }

        $this->acquiaPurgeVarnishForEnvironment($site, $environment);
    }

    /**
     * Clears varnish cache for all domains the production environment.
     *
     * @param string $site
     *
     * @command prod:purgevarnish
     */
    public function acquiaPurgeVarnishProd($site)
    {
        $this->yell('WARNING: CLEARNING PROD VARNISH CACHE CAN RESULT IN REDUCTION IN PERFORMANCE');
        if ($this->confirm('Are you sure you want to clear prod varnish cache?')) {
            $this->acquiaPurgeVarnishForEnvironment($site, 'prod');
        }
    }
}



