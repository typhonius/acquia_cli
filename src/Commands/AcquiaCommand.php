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

    const UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

    const taskFailed = 'failed';

    const taskCompleted = 'completed';

    const taskStarted = 'started';

    const taskInProgress = 'in-progress';

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

    protected function getUuidFromHostingName($name)
    {
        $applications = $this->cloudapi->applications();
        foreach ($applications as $application) {
            if ($name === $application->hosting->id) {
                return $application->uuid;
            }
        }
        throw new Exception('Unable to find UUID for application');
    }

    /**
     *
     * Since Acquia API v2 doesn't return the task ID when a task is created,
     * we must instead check the task list for tasks of a particular type
     * and ensure that they are all completed before assuming our task is done.
     *
     * It's a bit of a hack but there's not really another choice.
     *
     * @string $uuid
     * @param  $taskUuid
     * @return bool
     * @throws Exception
     */
    protected function waitForTask($uuid, $name)
    {

        // @TODO ensure tasks are sorted most recent first.

        $sleep = $this->extraConfig['taskwait'];
        $timeout = $this->extraConfig['timeout'];

        // Create a date 30 seconds prior to this function being called to
        // ensure we capture our task in the filter.
        $buffer = 30;

        $timezone = new \DateTimeZone('UTC');

        $start = new \DateTime(date('c'));
        $start->setTimezone($timezone);
        $start->sub(new \DateInterval('PT' . $buffer . 'S'));

        while (true) {
            $this->say('Waiting for task to complete...');
            // Sleep initially to ensure that the task gets registered.
            sleep($sleep);
            $this->cloudapi->addQuery('from', $start->format(\DateTime::ATOM));
            $tasks = $this->cloudapi->tasks($uuid);
            $this->cloudapi->clearQuery();

            if (!$count = count($tasks)) {
                throw new \Exception('No tasks registered.');
            }
            $started = 0;
            $completed = 0;

            foreach ($tasks as $task) {
                switch ($task->status) {
                    case self::taskFailed:
                        // If there's one failure we should throw an exception
                        // although it may not be for our task.
                        throw new \Exception('Acquia task failed.');
                        break;
                    case self::taskStarted:
                    case self::taskInProgress:
                        // If tasks are started, we should continue back to the
                        // top of the loop and wait until tasks are complete.
                        ++$started;
                        continue;
                    case self::taskCompleted:
                        // Completed tasks should break and continue execution.
                        ++$completed;
                        break;
                    default:
                        throw new \Exception('Unknown task status.');
                        break;
                }
            }
            // Break here if all tasks are completed.
            if ($count === $completed) {
                break;
            }

            // Create a new DateTime for now.
            $current = new \DateTime(date('c'));
            $current->setTimezone($timezone);
            // Remove our buffer from earlier that we took away from the original start date.
            $current->sub(new \DateInterval('PT' . $buffer . 'S'));
            if ($timeout <= ($current->getTimestamp() - $start->getTimestamp())) {
                throw new \Exception("Task timeout of ${timeout} seconds exceeded.");
            }
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
     * @param $uuid
     * @param $id
     */
    protected function backupAllEnvironmentDbs($uuid, $id)
    {
        $databases = $this->cloudapi->databases($uuid);
        foreach ($databases as $database) {
            $this->backupDb($uuid, $id, $database->name);
        }
    }

    /**
     * @param $uuid
     * @param $id
     * @param $database
     */
    protected function backupDb($uuid, $id, $database)
    {
        // Run database backups.
        $this->say("Backing up DB (${database}) on ${id}");
        $this->cloudapi->databaseBackup($id, $database);
        $this->waitForTask($uuid, 'DatabaseBackupCreated');
    }

    /**
     * @param $uuid
     * @param $idFrom
     * @param $idTo
     */
    protected function backupFiles($uuid, $idFrom, $idTo)
    {
        // Copy files from prod to non-prod.
        $this->say("Moving files from ${idFrom} to ${idTo}");
        $this->cloudapi->copyFiles($idFrom, $idTo);
        $this->waitForTask($uuid, 'FilesCopied');
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
