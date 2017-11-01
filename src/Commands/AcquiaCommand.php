<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\CloudApi\Client;
use Robo\Tasks;
use Robo\Robo;
use Exception;

/**
 * Class AcquiaCommand
 * @package AcquiaCli\Commands
 */
abstract class AcquiaCommand extends Tasks
{
    use \Boedah\Robo\Task\Drush\loadTasks;

    /** @var \AcquiaCloudApi\CloudApi\Client $cloudapi */
    protected $cloudapi;

    /** Additional configuration */
    protected $extraConfig;

    /**
     * AcquiaCommand constructor.
     */
    public function __construct()
    {
        $extraConfig = Robo::config()->get('extraconfig');
        $this->extraConfig = $extraConfig;

        $acquia = Robo::config()->get('acquia');
        $cloudapi = Client::factory(array(
            'key' => $acquia['key'],
            'secret' => $acquia['secret'],
        ));

        /** @var \AcquiaCloudApi\CloudApi\Client $cloudapi */
        $this->cloudapi = $cloudapi;
    }

    /**
     * @param $uuid
     * @param $environment
     * @return mixed
     * @throws Exception
     */
    protected function getIdFromEnvironmentName($uuid, $environment)
    {
        $environments = $this->cloudapi->environments($uuid);
        foreach ($environments as $e) {
            if ($environment === $e->name) {
                return $e->id;
            }
        }
        throw new Exception('Unable to find ID for environment');
    }

    /**
     * @string $site
     * @param Task $task
     * @return bool
     * @throws Exception
     * @throws ServerErrorResponseException
     */
    protected function waitForTask($site, Task $task)
    {
        $taskId = $task->id();
        $complete = false;

        while ($complete === false) {
            $this->say('Waiting for task to complete...');
            $task = $this->cloudapi->task($site, $taskId);
            if ($task->completed()) {
                if ($task->state() !== 'done') {
                    throw new \Exception('Acquia task failed.');
                }
                $complete = true;
                break;
            }
            $sleep = $this->extraConfig['taskwait'];
            sleep($sleep);
            // @TODO add a timeout here?
        }

        return true;
    }

    /**
     * @param $site
     * @param $environmentFrom
     * @param $environmentTo
     */
    protected function backupAndMoveDbs($site, $environmentFrom, $environmentTo)
    {
        $databases = $this->cloudapi->environmentDatabases($site, $environmentFrom);
        foreach ($databases as $database) {
            /** @var Database $database */
            $db = $database->name();

            $this->backupDb($site, $environmentTo, $database);

            // Copy DB from prod to non-prod.
            $this->say("Moving DB (${db}) from ${environmentFrom} to ${environmentTo}");
            $task = $this->cloudapi->copyDatabase($site, $db, $environmentFrom, $environmentTo);
            $this->waitForTask($site, $task);
        }
    }

    /**
     * @param $site
     * @param $environment
     */
    protected function backupAllEnvironmentDbs($site, $environment)
    {
        $databases = $this->cloudapi->environmentDatabases($site, $environment);
        foreach ($databases as $database) {
            $this->backupDb($site, $environment, $database);
        }
    }

    /**
     * @param $site
     * @param $environment
     * @param Database $database
     */
    protected function backupDb($site, $environment, Database $database)
    {
        // Run database backups.
        $dbName = $database->name();
        $this->say("Backing up DB (${dbName}) on ${environment}");
        $task = $this->cloudapi->createDatabaseBackup($site, $environment, $dbName);
        $this->waitForTask($site, $task);
    }

    /**
     * @param $site
     * @param $environmentFrom
     * @param $environmentTo
     */
    protected function backupFiles($site, $environmentFrom, $environmentTo)
    {
        // Copy files from prod to non-prod.
        $this->say("Moving files from ${environmentFrom} to ${environmentTo}");
        $task = $this->cloudapi->copyFiles($site, $environmentFrom, $environmentTo);
        $this->waitForTask($site, $task);
    }

    /**
     * @param $site
     * @param $environment
     * @param $branch
     */
    protected function acquiaDeployEnv($site, $environment, $branch)
    {
        $this->backupAllEnvironmentDbs($site, $environment);
        $this->say("Deploying ${branch} to the ${environment} environment");
        $deployTask = $this->cloudapi->pushCode($site, $environment, $branch);
        $this->waitForTask($site, $deployTask);
        $this->acquiaConfigUpdate($site, $environment);
    }

    /**
     * @param $site
     * @param $environment
     */
    protected function acquiaConfigUpdate($site, $environment)
    {
        $site = $this->cloudapi->site($site);
        $siteName = $site->unixUsername();

        $this->taskDrushStack()
            ->stopOnFail()
            ->siteAlias("@${siteName}.${environment}")
            ->clearCache('drush')
            ->drush('state-set system.maintenance_mode 1')
            ->drush('cache-rebuild')
            ->updateDb()
            ->drush(['pm-enable', 'config_split'])
            ->drush(['config-import', 'sync'])
            ->drush('cache-rebuild')
            ->drush('state-set system.maintenance_mode 0')
            ->run();
    }

    /**
     * @param $site
     * @param $environment
     */
    protected function acquiaPurgeVarnishForEnvironment($site, $environment)
    {
        $domains = $this->cloudapi->domains($site, $environment);
        /** @var Domain $domain */
        foreach ($domains as $domain) {
            $domainName = $domain->name();
            $this->say("Purging varnish cache for ${domainName} in ${environment} environment");
            $task = $this->cloudapi->purgeVarnishCache($site, $environment, $domainName);
            $this->waitForTask($site, $task);
        }
    }
}
