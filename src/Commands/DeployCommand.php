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

    protected function acquiaDeployEnv($site, $environment) {
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
    }

    /**
     * This is the acquia:preproddeployenv command
     *
     * @command acquia:deploy:preprod:env
     */
    public function acquiaDeployPreProdEnv($site, $environment) {
        if ($environment == 'prod') {
            throw new \Exception('Use the acquia:proddeploy command for the production environment.');
        }

        $this->acquiaDeployEnv($site, $environment);
    }

    /**
     * This is the acquia:preproddeploy command
     *
     * @command acquia:deploy:preprod
     */
    public function acquiaDeployPreProd($site) {
        $environments = $this->cloudapi->environments($site);

        foreach ($environments as $environment) {
            $env = $environment->name();
            if ($env == 'prod') {
                continue;
            }

            $this->acquiaDeployEnv($site, $env);
        }
    }

    /**
     * This is the acquia:proddeploy command
     *
     * @command acquia:deploy:prod
     */
    public function acquiaDeployProd($site) {
        $this->yell('WARNING: DEPLOYING TO PROD');
        if ($this->confirm('Are you sure you want to deploy to prod?')) {
            $this->acquiaDeployEnv($site, 'prod');
        }
    }

    /**
     * This is the acquia:sites command
     *
     * @command acquia:site:list
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
     * @command acquia:site:info
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

    /**
     * This is the acquia:tasks command
     *
     * @command acquia:task:list
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
     * @command acquia:task:info
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

