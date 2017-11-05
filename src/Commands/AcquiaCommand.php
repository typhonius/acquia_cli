<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\CloudApi\Client;
use AcquiaCloudApi\Response\DatabaseResponse;
use AcquiaCloudApi\Response\EnvironmentResponse;
use Consolidation\AnnotatedCommand\CommandData;
use Psr\Http\Message\StreamInterface;
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
     * Replace application names and environment names with UUIDs before
     * commands run.
     *
     * @hook validate
     *
     * @param CommandData $commandData
     */
    public function validateUuidHook(CommandData $commandData)
    {
        // @TODO alter the application ID to automatically add prod: or devcloud:

        if ($commandData->input()->hasArgument('uuid')) {
            $uuid = $commandData->input()->getArgument('uuid');

            if (!preg_match(self::UUIDv4, $uuid)) {
                $uuid = $this->getUuidFromHostingName($uuid);
                $commandData->input()->setArgument('uuid', $uuid);
            }

            if ($commandData->input()->hasArgument('environment')) {
                $environmentName = $commandData->input()->getArgument('environment');
                $environment = $this->getEnvironmentFromEnvironmentName($uuid, $environmentName);
                $commandData->input()->setArgument('environment', $environment);
            }
            if ($commandData->input()->hasArgument('environmentFrom')) {
                $environmentFromName = $commandData->input()->getArgument('environmentFrom');
                $environmentFrom = $this->getEnvironmentFromEnvironmentName($uuid, $environmentFromName);
                $commandData->input()->setArgument('environmentFrom', $environmentFrom);
            }
            if ($commandData->input()->hasArgument('environmentTo')) {
                $environmentToName = $commandData->input()->getArgument('environmentTo');
                $environmentTo = $this->getEnvironmentFromEnvironmentName($uuid, $environmentToName);
                $commandData->input()->setArgument('environmentTo', $environmentTo);
            }
        }
    }

    /**
     * @param string $uuid
     * @param string $environment
     * @return mixed
     * @throws Exception
     */
    protected function getEnvironmentFromEnvironmentName($uuid, $environment)
    {
        $environments = $this->cloudapi->environments($uuid);
        foreach ($environments as $e) {
            if ($environment === $e->name) {
                return $e;
            }
        }
        throw new Exception('Unable to find ID for environment');
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
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
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     */
    protected function backupAndMoveDbs($uuid, $environmentFrom, $environmentTo)
    {
        $databases = $this->cloudapi->environmentDatabases($environmentFrom);
        foreach ($databases as $database) {
            $this->backupDb($uuid, $environmentTo, $database);
            $dbName = $database->name;

            // Copy DB from prod to non-prod.
            $this->say("Moving DB (${dbName}) from ${environmentFrom} to ${environmentTo}");
            $this->cloudapi->databaseCopy($environmentTo->id, $dbName, $environmentFrom->id);
            $this->waitForTask($uuid, 'DatabaseCopied');
        }
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     */
    protected function backupAllEnvironmentDbs($uuid, EnvironmentResponse $environment)
    {
        $databases = $this->cloudapi->databases($uuid);
        foreach ($databases as $database) {
            $this->backupDb($uuid, $environment, $database);
        }
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param DatabaseResponse    $database
     */
    protected function backupDb($uuid, EnvironmentResponse $environment, DatabaseResponse $database)
    {
        // Run database backups.
        $label = $environment->label;
        $dbName = $database->name;
        $this->say("Backing up DB (${dbName}) on ${label}");
        $this->cloudapi->databaseBackup($environment->uuid, $database->name);
        $this->waitForTask($uuid, 'DatabaseBackupCreated');
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     */
    protected function backupFiles($uuid, EnvironmentResponse $environmentFrom, EnvironmentResponse $environmentTo)
    {
        // Copy files from prod to non-prod.
        $labelFrom = $environmentFrom->label;
        $labelTo = $environmentTo->label;
        $this->say("Moving files from ${labelFrom} to ${labelTo}");
        $this->cloudapi->copyFiles($environmentFrom->uuid, $environmentTo->uuid);
        $this->waitForTask($uuid, 'FilesCopied');
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $branch
     */
    protected function acquiaDeployEnv($uuid, EnvironmentResponse $environment, $branch)
    {
        $this->backupAllEnvironmentDbs($uuid, $environment);
        $label = $environment->label;
        $this->say("Deploying ${branch} to the ${label} environment");

        $this->cloudapi->switchCode($environment->uuid, $branch);

        $this->waitForTask($uuid, 'CodeSwitched');
        $this->acquiaConfigUpdate($environment);
    }

    /**
     * @param EnvironmentResponse $environment
     */
    protected function acquiaConfigUpdate($environment)
    {
        $sshUrl = $environment->sshUrl;
        $drushAlias = strtok($sshUrl, '@');

        $this->taskDrushStack()
            ->stopOnFail()
            ->siteAlias("@${drushAlias}")
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
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     */
    protected function acquiaPurgeVarnishForEnvironment($uuid, EnvironmentResponse $environment)
    {
        $domains = $this->cloudapi->domains($environment->uuid);

        $domainsList = array_map(function($domain) {
            $hostname = $domain->hostname;
            $this->say("Purging varnish cache for ${hostname}");

            return $hostname;
        }, $domains->getArrayCopy());

        $this->cloudapi->purgeVarnishCache($environment->uuid, $domainsList);
        $this->waitForTask($uuid, 'VarnishCleared');
    }
}
