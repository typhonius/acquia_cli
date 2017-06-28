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

    /**
     * This is the acquia:deploy command
     *
     * @command acquia:deploy
     */
    public function acquiaDeploy($site, $environment) {
        $task = $this->taskDrushStack()
            ->stopOnFail()
            ->siteAlias("@${site}.${environment}")
            ->clearCache('drush')
            ->drush("cache-rebuild")
            ->updateDb()
            ->drush(['pm-enable', 'config_split'])
            ->drush(['config-import', 'sync'])
            ->drush("cache-rebuild")
            ->run();
    }

    /**
     * This is the acquia:sites command
     *
     * @command acquia:sites
     */
    public function acquiaSites()
    {
        $sites = $this->cloudapi->sites();
        foreach ($sites as $site) {
            $this->say($site->name());
        }
    }

    /**
     * This is the acquia:siteinfo command
     *
     * @command acquia:siteinfo
     */
    public function acquiaSiteInfo($site)
    {
        $site = $this->cloudapi->site($site);
        $environments = $this->cloudapi->environments($site);
        
        $this->say('Name: ' . $site->title());
        $this->say('VCS URL: ' . $site->vcsUrl());

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('Environment', 'Branch/Tag', 'Domain', 'Database(s)'));

        foreach ($environments as $environment) {
            $databases = $this->cloudapi->environmentDatabases($site, $environment);
            $dbs = [];
            foreach ($databases as $database) {
                $dbs[] = $database->name();
            }
            $dbString = implode(', ', $dbs);
            $table
                ->addRows(array(
                    array($environment->name(), $environment->vcsPath(), $environment->defaultDomain(), $dbString),
                ));
        }
        $table->render();

    }

    /**
     * This is the acquia:preprodprep command
     *
     * @command acquia:preprodprep
     */
    public function acquiaPreProdPrep($site)
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

    /**
     * This is the acquia:tasks command
     *
     * @command acquia:tasks
     */
    public function acquiaTasks($site) {

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('ID', 'User', 'State', 'Description'));

        $tasks = $this->cloudapi->tasks($site);
        foreach ($tasks as $task) {
            $table
                ->addRows(array(
                    array($task->id(), $task->sender(), $task->state(), $task->description()),
                ));
        }

        $table->render();
    }

    /**
     * This is the acquia:task command
     *
     * @command acquia:task
     */
    public function acquiaTask($site, $taskId) {
        $tz = 'Australia/Sydney';

        $task = $this->cloudapi->task($site, $taskId);
        $startedDate = new \DateTime();
        $startedDate->setTimestamp($task->startTime());
        $startedDate->setTimezone(new \DateTimeZone($tz));
        $completedDate = new \DateTime();
        $completedDate->setTimestamp($task->startTime());
        $completedDate->setTimezone(new \DateTimeZone($tz));
        $task->created()->setTimezone(new \DateTimeZone($tz));

        $this->say('ID: ' . $task->id());
        $this->say('Sender: ' . $task->sender());
        $this->say('Description: ' . $task->description());
        $this->say('State: ' . $task->state());
        $this->say('Created: ' . $task->created()->format('Y-m-d H:i:s'));
        $this->say('Started: ' . $startedDate->format('Y-m-d H:i:s'));
        $this->say('Completed: ' . $completedDate->format('Y-m-d H:i:s'));
        $this->say('Logs: ' . $task->logs());
    }

}

