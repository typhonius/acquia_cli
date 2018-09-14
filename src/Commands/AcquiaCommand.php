<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\CloudApi\Client;
use AcquiaCloudApi\CloudApi\Connector;
use AcquiaCloudApi\Response\DatabaseResponse;
use AcquiaCloudApi\Response\EnvironmentResponse;
use Consolidation\AnnotatedCommand\CommandData;
use Robo\Tasks;
use Robo\Robo;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Class AcquiaCommand
 * @package AcquiaCli\Commands
 */
abstract class AcquiaCommand extends Tasks
{
    use \Boedah\Robo\Task\Drush\loadTasks;

    /** @var \AcquiaCloudApi\CloudApi\Client $cloudapi */
    protected $cloudapi;

    /** Additional configuration. */
    protected $extraConfig;

    /** Regex for a valid UUID string. */
    const UUIDV4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

    /** Task response from API indicates failure. */
    const TASKFAILED = 'failed';

    /** Task response from API indicates completion. */
    const TASKCOMPLETED = 'completed';

    /** Task response from API indicates started. */
    const TASKSTARTED = 'started';

    /** Task response from API indicates in progress. */
    const TASKINPROGRESS = 'in-progress';

    /**
     * AcquiaCommand constructor.
     */
    public function __construct()
    {
        $extraConfig = Robo::config()->get('extraconfig');
        $this->extraConfig = $extraConfig;

        $acquia = Robo::config()->get('acquia');
        $connector = new Connector([
            'key' => $acquia['key'],
            'secret' => $acquia['secret'],
        ]);
        $cloudapi = Client::factory($connector);

        /** @var \AcquiaCloudApi\CloudApi\Client $cloudapi */
        $this->cloudapi = $cloudapi;
    }

    /**
     * Override the confirm method from consolidation/Robo to allow automatic
     * confirmation.
     *
     * @param string $question
     */
    protected function confirm($question)
    {
        if ($this->input()->getOption('yes')) {
            $this->say('Ignoring confirmation question as --yes option passed.');

            return true;
        }

        return parent::confirm($question);
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
        if ($commandData->input()->hasArgument('uuid')) {
            $uuid = $commandData->input()->getArgument('uuid');

            // Detect if a UUID has been passed in or a sitename.
            if (!preg_match(self::UUIDV4, $uuid)) {
                // Detect if this is not a fully qualified Acquia sitename e.g. prod:acquia
                if (strpos($uuid, ':') === false) {
                    // Use a realm passed in from the command line e.g. --realm=devcloud.
                    // If no realm is specified, 'prod:' will be prepended by default.
                    if ($commandData->input()->hasOption('realm')) {
                        $uuid = $commandData->input()->getOption('realm') . ':' . $uuid;
                    }
                }
                $uuid = $this->getUuidFromHostingName($uuid);
                $commandData->input()->setArgument('uuid', $uuid);
            }

            // Convert environment parameters to an EnvironmentResponse
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
     * @return EnvironmentResponse
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
     * @param  $name
     * @return bool
     * @throws Exception
     */
    protected function waitForTask($uuid, $name)
    {
        if ($this->input()->getOption('no-wait')) {
            $this->say('Skipping wait for task.');
            return true;
        }

        $sleep = $this->extraConfig['taskwait'];
        $timeout = $this->extraConfig['timeout'];

        // Create a date 30 seconds prior to this function being called to
        // ensure we capture our task in the filter.
        $buffer = 30;

        // Acquia servers all use UTC so ensure we use the right timezone.
        $timezone = new \DateTimeZone('UTC');

        $start = new \DateTime(date('c'));
        $start->setTimezone($timezone);
        $start->sub(new \DateInterval('PT' . $buffer . 'S'));

        // Kindly stolen from https://jonczyk.me/2017/09/20/make-cool-progressbar-symfony-command/
        $output = $this->output();
        $progress = new ProgressBar($output);
        $progress->setBarCharacter('<fg=green>⚬</>');
        $progress->setEmptyBarCharacter('<fg=red>⚬</>');
        $progress->setProgressCharacter('<fg=green>➤</>');
        $progress->setFormat("<fg=white;bg=cyan> %message:-45s%</>\n%elapsed:6s% [%bar%] %percent:3s%%");

        $progress->start();
        $progress->setMessage('Looking up task');

        while (true) {
            $progress->advance($sleep);
            // Sleep initially to ensure that the task gets registered.
            sleep($sleep);
            // Add queries to limit the tasks returned to a single task of a specific name created within the last 30s.
            $this->cloudapi->addQuery('from', $start->format(\DateTime::ATOM));
            $this->cloudapi->addQuery('sort', '-created');
            $this->cloudapi->addQuery('limit', 1);
            $this->cloudapi->addQuery('filter', "name=${name}");

            $tasks = $this->cloudapi->tasks($uuid);
            $this->cloudapi->clearQuery();

            if (!$task = reset($tasks)) {
                throw new \Exception('No tasks registered. 
The task may have been queried prior to being registered within the API.
This may be due to the wait timeout being set too low in the Acquia Cli configuration file.');
            }

            $progress->setMessage('Task ' . $task->status);
            switch ($task->status) {
                case self::TASKFAILED:
                    // If there's one failure we should throw an exception
                    // although it may not be for our task.
                    throw new \Exception('Acquia task failed.');
                    break(2);
                // If tasks are started or in progress, we should continue back
                // to the top of the loop and wait until tasks are complete.
                case self::TASKSTARTED:
                case self::TASKINPROGRESS:
                    break;
                case self::TASKCOMPLETED:
                    // Completed tasks should break out of the loop and continue execution.
                    break(2);
                default:
                    throw new \Exception('Unknown task status.');
                    break(2);
            }

            // Timeout if the command exceeds the configured timeout threshold.
            // Create a new DateTime for now.
            $current = new \DateTime(date('c'));
            $current->setTimezone($timezone);
            // Remove our buffer from earlier that we took away from the original start date.
            $current->sub(new \DateInterval('PT' . $buffer . 'S'));
            // Take our current time, remove our start time and see if it exceeds the timeout.
            if ($timeout <= ($current->getTimestamp() - $start->getTimestamp())) {
                throw new \Exception("Task timeout of ${timeout} seconds exceeded.");
            }
        }
        $progress->finish();
        $this->writeln(PHP_EOL);

        return true;
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     */
    protected function backupAndMoveDbs($uuid, $environmentFrom, $environmentTo)
    {
        $environmentFromLabel = $environmentFrom->label;
        $environmentToLabel = $environmentTo->label;

        $databases = $this->cloudapi->environmentDatabases($environmentFrom->uuid);
        foreach ($databases as $database) {
            $this->backupDb($uuid, $environmentTo, $database);
            $dbName = $database->name;

            // Copy DB from prod to non-prod.
            $this->say("Moving DB (${dbName}) from ${environmentFromLabel} to ${environmentToLabel}");

            $this->cloudapi->databaseCopy($environmentFrom->uuid, $dbName, $environmentTo->uuid);
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
        $this->cloudapi->createDatabaseBackup($environment->uuid, $database->name);
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
     * @param bool                $skipDrushTasks
     */
    protected function acquiaDeployEnv($uuid, EnvironmentResponse $environment, $branch, $skipDrushTasks)
    {
        $this->backupAllEnvironmentDbs($uuid, $environment);
        $label = $environment->label;
        $this->say("Deploying ${branch} to the ${label} environment");

        $this->cloudapi->switchCode($environment->uuid, $branch);
        $this->waitForTask($uuid, 'CodeSwitched');

        if ($skipDrushTasks === true) {
            $this->say('Skipping Drush tasks.');
            return true;
        }

        $this->acquiaConfigUpdate($environment);
    }

    /**
     * @param EnvironmentResponse $environment
     */
    protected function acquiaConfigUpdate($environment)
    {
        $sshUrl = $environment->sshUrl;
        $drushAlias = strtok($sshUrl, '@');

        $syncDir = $this->extraConfig['configsyncdir'];

        $this->taskDrushStack()
            ->stopOnFail()
            ->siteAlias("@${drushAlias}")
            ->clearCache('drush')
            ->drush('state-set system.maintenance_mode 1')
            ->drush('cache-rebuild')
            ->updateDb()
            ->drush(['pm-enable', 'config_split'])
            ->drush(['config-import', $syncDir])
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

        $domainsList = array_map(function ($domain) {
            $hostname = $domain->hostname;
            $this->say("Purging varnish cache for ${hostname}");

            return $hostname;
        }, $domains->getArrayCopy());

        $this->cloudapi->purgeVarnishCache($environment->uuid, $domainsList);
        $this->waitForTask($uuid, 'VarnishCleared');
    }
}
