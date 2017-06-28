<?php

namespace AcquiaCli\Commands;

use Robo\Tasks;
use Robo\Robo;
use Acquia\Cloud\Api\CloudApiClient;
use Symfony\Component\Console\Helper\Table;

class DeployCommand extends Tasks
{
    /** @var CloudApiClient $cloudapi */
    protected $cloudapi;

    use \Boedah\Robo\Task\Drush\loadTasks;

    /**
     * This hook will fire for all commands in this command file.
     *
     * @hook init
     */
    public function construct() {
        $acquia = Robo::Config()->get('acquia');
        $cloudapi = CloudApiClient::factory(array(
            'username' => $acquia['mail'],
            'password' => $acquia['pass'],
        ));

        $this->cloudapi = $cloudapi;
    }

    protected function isTaskComplete($site, $taskId) {
        $task = $this->cloudapi->task($site, $taskId);
        if ($task->completed()) {
            return TRUE;
        }
        return FALSE;
    }

    protected function acquiaDeployEnv($site, $environment, $branch)
    {
        $task = $this->cloudapi->pushCode($site, $environment, $branch);
        $taskId = $task->id();
        while (!$this->isTaskComplete($site, $taskId)) {
            $this->say('Waiting for code deployment...');
            sleep(1);
        }
        $this->acquiaReDeployEnv($site, $environment);
    }


    protected function acquiaReDeployEnv($site, $environment) {
        $site = $this->cloudapi->site($site);
        $siteName = $site->unixUsername();

        $task = $this->taskDrushStack()
            ->stopOnFail()
            ->siteAlias("@${siteName}.${environment}")
            ->clearCache('drush')
            ->drush("cache-rebuild")
            ->updateDb()
            ->drush(['pm-enable', 'config_split'])
            ->drush(['config-import', 'sync'])
            ->drush("cache-rebuild")
            ->run();

        // @TODO add domains
        //$this->cloudapi->purgeVarnishCache($site, $environment);
    }

    /**
     * This is the acquia:preproddeployenv command
     *
     * @command acquia:deploy:preprod:env
     */
    public function acquiaDeployPreProdEnv($site, $environment, $branch) {
        if ($environment == 'prod') {
            throw new \Exception('Use the acquia:proddeploy command for the production environment.');
        }

        $this->acquiaDeployEnv($site, $environment, $branch);
    }

    /**
     * This is the acquia:deploy:preprod command
     *
     * @command acquia:redeploy:preprod:all
     */
    public function acquiaDeployPreProd($site) {
        $environments = $this->cloudapi->environments($site);

        foreach ($environments as $environment) {
            $env = $environment->name();
            if ($env == 'prod') {
                continue;
            }

            $this->acquiaReDeployEnv($site, $env);
        }
    }

    /**
     * This is the acquia:deploy:prod command
     *
     * @command acquia:deploy:prod
     */
    public function acquiaDeployProd($site, $branch) {
        $this->yell('WARNING: DEPLOYING TO PROD');
        if ($this->confirm('Are you sure you want to deploy to prod?')) {
            $this->acquiaDeployEnv($site, 'prod', $branch);
        }
    }

    /**
     * This is the acquia:preprodprep command
     *
     * @command acquia:prepare:preprod
     */
    public function acquiaPreparePreProd($site)
    {
        $environments = $this->cloudapi->environments($site);
        foreach ($environments as $environment) {
            $env = $environment->name();
            if ($env == 'prod') {
                continue;
            }
            $databases = $this->cloudapi->environmentDatabases($site, $environment);
            foreach ($databases as $database) {

                // Run database backups.
                $db = $database->name();
                $this->say("Backing up DB (${db}) on ${environment}");
                $this->cloudapi->createDatabaseBackup($site, $environment, $db);

                // Copy DB from prod to non-prod.
                $this->say("Moving DB (${db}) from prod to ${env}");
                $this->cloudapi->copyDatabase($site, $db, 'prod', $env);
            }

            // Copy files from prod to non-prod.
            $this->say("Moving files from prod to ${env}");
            $this->cloudapi->copyFiles($site, 'prod', $env);
        }
    }
}

