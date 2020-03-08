<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Connector\Client;
use AcquiaCloudApi\Connector\Connector;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Organizations;
use AcquiaCloudApi\Endpoints\Notifications;
use AcquiaCloudApi\Endpoints\Databases;
use AcquiaCloudApi\Endpoints\DatabaseBackups;
use AcquiaCloudApi\Response\DatabaseResponse;
use AcquiaCloudApi\Response\EnvironmentResponse;
use AcquiaCloudApi\Response\OperationResponse;
use AcquiaCloudApi\Response\OrganizationResponse;
use Consolidation\AnnotatedCommand\CommandData;
use Robo\Tasks;
use Robo\Robo;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Consolidation\AnnotatedCommand\AnnotationData;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class AcquiaCommand
 * @package AcquiaCli\Commands
 */
abstract class AcquiaCommand extends Tasks
{
    // @TODO https://github.com/boedah/robo-drush/issues/18
    //use \Boedah\Robo\Task\Drush\loadTasks;

    protected $cloudapiService;

    protected $cloudapi;

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
        $this->cloudapi = Robo::service('client');
        $this->cloudapiService = Robo::service('cloudApi');

        $this->setTableStyles();
    }

    /**
     * Override the confirm method from consolidation/Robo to allow automatic
     * confirmation.
     *
     * @param string $question
     * @param bool $default
     */
    protected function confirm($question, $default = false)
    {
        if ($this->input()->getOption('yes')) {
            // @TODO add this back in later.
            // $this->say('Ignoring confirmation question as --yes option passed.');

            return true;
        }

        return parent::confirm($question, $default);
    }

    /**
     * Adds sort, limit, and filer options to the CloudAPI request.
     *
     * @hook init
     *
     * @param InputInterface $input
     * @param AnnotationData $annotationData
     */
    public function initApiOptionsHook(InputInterface $input, AnnotationData $annotationData)
    {
        if ($limit = $input->getOption('limit')) {
            $this->cloudapi->addQuery('limit', $limit);
        }
        if ($sort = $input->getOption('sort')) {
            $this->cloudapi->addQuery('sort', $sort);
        }
        if ($filter = $input->getOption('filter')) {
            $this->cloudapi->addQuery('filter', $filter);
        }
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

        // Not super ideal to use this, however this is required until I can work
        // out a better way.
        // We need to exit early if we're running tests as we don't want this step
        // to fire on each command tested. This makes it really hard to mock because
        // the input to our command changes from what the user specifies based on
        // this validate hook.
        // This is great functionality for the user as they don't need to remember
        // UUIDs, however it's difficult to test.
        // @TODO see if there is a way to dynamically turn off hooks in
        // consolidation/annotated-command
        if (defined('PHPUNIT_ACQUIACLI_TESTSUITE') && PHPUNIT_ACQUIACLI_TESTSUITE) {
            return;
        }

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
                $uuid = $this->cloudapiService->getApplicationUuid($uuid);
                $commandData->input()->setArgument('uuid', $uuid);
            }
        }
    }

    /**
     * Waits for a notification to complete.
     *
     * @param OperationResponse $response
     * @throws \Exception
     */
    protected function waitForNotification($response)
    {
        if ($this->input()->getOption('no-wait')) {
            // @TODO put this back in later.
            // $this->say('Skipping wait for notification.');
            return true;
        }

        $notificationArray = explode('/', $response->links->notification->href);
        if (empty($notificationArray)) {
            throw new \Exception('Notification UUID not found.');
        }
        $notificationUuid = end($notificationArray);

        $extraConfig = $this->cloudapiService->getExtraConfig();
        $sleep = $extraConfig['taskwait'];
        $timeout = $extraConfig['timeout'];

        $timezone = new \DateTimeZone('UTC');
        $start = new \DateTime(date('c'));
        $start->setTimezone($timezone);

        $progress = $this->getProgressBar();
        $progress->setFormat("<fg=white;bg=cyan> %message:-45s%</>\n%elapsed:6s% [%bar%] %percent:3s%%");
        $progress->setMessage('Looking up notification');
        $progress->start();

        $notificationAdapter = new Notifications($this->cloudapi);

        while (true) {
            $progress->advance($sleep);
            // Sleep initially to ensure that the task gets registered.
            sleep($sleep);

            $notification = $notificationAdapter->get($notificationUuid);

            $progress->setMessage('Notification ' . $notification->status);
            switch ($notification->status) {
                case self::TASKFAILED:
                    // If there's one failure we should throw an exception
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
                    throw new \Exception('Unknown notification status.');
                    break(2);
            }

            // Timeout if the command exceeds the configured timeout threshold.
            // Create a new DateTime for now.
            $current = new \DateTime(date('c'));
            $current->setTimezone($timezone);
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
    protected function backupAndMoveDbs($uuid, $environmentFrom, $environmentTo, $dbName = null)
    {
        if (null !== $dbName) {
            $this->cloudapi->addQuery('filter', "name=${dbName}");
        }

        $dbAdapter = new Databases($this->cloudapi);
        $databases = $dbAdapter->getAll($uuid);
        $this->cloudapi->clearQuery();

        foreach ($databases as $database) {
            $this->backupDb($uuid, $environmentTo, $database);

            // Copy DB from prod to non-prod.
            $this->say(
                sprintf(
                    'Moving DB (%s) from %s to %s',
                    $database->name,
                    $environmentFrom->label,
                    $environmentTo->label
                )
            );

            $databaseAdapter = new Databases($this->cloudapi);
            $response = $databaseAdapter->copy($environmentFrom->uuid, $database->name, $environmentTo->uuid);
            $this->waitForNotification($response);
        }
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     */
    protected function backupAllEnvironmentDbs($uuid, $environment)
    {
        $dbAdapter = new Databases($this->cloudapi);
        // var_dump($dbAdapter);
        $databases = $dbAdapter->getAll($uuid);
        // var_dump($databases);
        foreach ($databases as $database) {
            $this->backupDb($uuid, $environment, $database);
        }
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param DatabaseResponse    $database
     */
    protected function backupDb($uuid, $environment, $database)
    {
        // var_dump($database);
        // Run database backups.
        $this->say(sprintf('Backing up DB (%s) on %s', $database->name, $environment->label));
        $dbAdapter = new DatabaseBackups($this->cloudapi);
        $response = $dbAdapter->create($environment->uuid, $database->name);
        $this->waitForNotification($response);
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     */
    protected function copyFiles($uuid, $environmentFrom, $environmentTo)
    {
        $environmentsAdapter = new Environments($this->cloudapi);
        $this->say(sprintf('Copying files from %s to %s', $environmentFrom->label, $environmentTo->label));
        $response = $environmentsAdapter->copyFiles($environmentFrom->uuid, $environmentTo->uuid);
        $this->waitForNotification($response);
    }

    protected function setClientOptions()
    {
        // if ($sort = $this->input()->getOption('sort')) {
        //     var_dump($sort);
        // }
        // if ($this->input()->getOption('filter')) {
        // }
        // $x= $this->input();
        // $x->getOption('limit');
        // if ($this->input()->getOption('limit')) {
        // }
    }

    protected function setTableStyles()
    {
        $tableStyle = new TableStyle();
        $tableStyle->setPadType(STR_PAD_BOTH);
        Table::setStyleDefinition('center-align', $tableStyle);
    }

    protected function getProgressBar()
    {
        // Kindly stolen from https://jonczyk.me/2017/09/20/make-cool-progressbar-symfony-command/
        $output = $this->output();
        $progressBar = new ProgressBar($output);
        $progressBar->setBarCharacter('<fg=green>⚬</>');
        $progressBar->setEmptyBarCharacter('<fg=red>⚬</>');
        $progressBar->setProgressCharacter('<fg=green>➤</>');

        return $progressBar;
    }
}
