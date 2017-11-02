<?php

namespace AcquiaCli\Commands;

use Symfony\Component\Console\Helper\Table;

/**
 * Class InfoCommand
 * @package AcquiaCli\Commands
 */
class InfoCommand extends AcquiaCommand
{

    /**
     * Gets all code branches and tags associated with an application.
     *
     * @param string $uuid
     * @param string $match
     *
     * @command code:list
     */
    public function code($uuid, $match = null)
    {
        if (!preg_match(self::UUIDv4, $uuid)) {
            $uuid = $this->getUuidFromHostingName($uuid);
        }
        if (null !== $match) {
            $this->cloudapi->addQuery('filter', "name=@*${match}*");
        }
        $code = $this->cloudapi->code($uuid);
        $this->cloudapi->clearQuery();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('Name', 'Tag'));

        foreach ($code as $branch) {
            $tag = $branch->flags->tag ? '✅' : '';
            $table
                ->addRows(array(
                    array($branch->name, $tag),
                ));
        }

        $table->render();
    }

    /**
     * Gets all tasks associated with a site.
     *
     * @param string $uuid
     *
     * @command task:list
     * @alias t:l
     */
    public function acquiaTasks($uuid)
    {

        if (!preg_match(self::UUIDv4, $uuid)) {
            $uuid = $this->getUuidFromHostingName($uuid);
        }

        $tasks = $this->cloudapi->tasks($uuid);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('ID', 'Name', 'Status', 'User'));

        foreach ($tasks as $task) {
            $table
                ->addRows(array(
                    array($task->uuid, $task->name, $task->status, $task->user->mail),
                ));
        }

        $table->render();
    }

    /**
     * Gets detailed information about a specific task
     *
     * @param string $uuid
     * @param string $taskUuid
     *
     * @command task:info
     * @alias t:i
     * @throws \Exception
     */
    public function acquiaTask($uuid, $taskUuid)
    {

        if (!preg_match(self::UUIDv4, $uuid)) {
            $uuid = $this->getUuidFromHostingName($uuid);
        }

        $tz = $this->extraConfig['timezone'];
        $format = $this->extraConfig['format'];

        $tasks = $this->cloudapi->tasks($uuid);

        foreach ($tasks as $task) {
            if ($taskUuid === $task->uuid) {

                $timezone = new \DateTimeZone($tz);

                $createdDate = new \DateTime($task->created_at);
                $startedDate = new \DateTime($task->started_at);
                $completedDate = new \DateTime($task->completed_at);

                $createdDate->setTimezone($timezone);
                $startedDate->setTimezone($timezone);
                $completedDate->setTimezone($timezone);

                $this->say('ID: ' . $task->uuid);
                $this->say('Sender: ' . $task->user->mail);
                $this->say('Description: ' . htmlspecialchars_decode($task->description));
                $this->say('Status: ' . $task->status);
                $this->say('Created: ' . $createdDate->format($format));
                $this->say('Started: ' . $startedDate->format($format));
                $this->say('Completed: ' . $completedDate->format($format));

                return;
            }
        }
        throw new \Exception('Unable to find Task ID');
    }

    /**
     * Shows all sites a user has access to.
     *
     * @command application:list
     * @alias app:list
     * @alias a:l
     */
    public function acquiaApplications()
    {
        $applications = $this->cloudapi->applications();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('Name', 'UUID', 'Hosting ID'));
        foreach ($applications as $application) {
            $table
                ->addRows(array(
                    array($application->name, $application->uuid, $application->hosting->id),
                ));
        }
        $table->render();
    }

    /**
     * Shows detailed information about a site.
     *
     * @param string $uuid
     *
     * @command application:info
     * @alias app:info
     * @alias a:i
     */
    public function acquiaApplicationInfo($uuid)
    {
        if (!preg_match(self::UUIDv4, $uuid)) {
            $uuid = $this->getUuidFromHostingName($uuid);
        }

        $environments = $this->cloudapi->environments($uuid);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('Environment', 'ID', 'Branch/Tag', 'Domain(s)', 'Database(s)'));

        foreach ($environments as $environment) {
            $vcs = $environment->vcs->path;

            $databases = $this->cloudapi->environmentDatabases($environment->id);

            $dbs = [];
            foreach ($databases as $database) {
                $dbs[] = $database->name;
            }
            $dbString = implode(', ', $dbs);

            $environmentName = $environment->label . ' (' . $environment->name . ')' ;
            if ($environment->flags->livedev) {
                $environmentName = '💻  ' . $environmentName;
            }

            if ($environment->flags->production_mode) {
                $environmentName = '🔒  ' . $environmentName;
            }

            $table
                ->addRows(array(
                    array($environmentName, $environment->id, $vcs, implode("\n", $environment->domains), $dbString),
                ));
        }
        $table->render();
        $this->say('💻  indicates environment in livedev mode.');
        $this->say('🔒  indicates environment in production mode.');

    }

    /**
     * Shows detailed information about servers in an environment.
     *
     * @param string      $uuid
     * @param string|null $environment
     *
     * @command environment:info
     * @alias env:info
     * @alias e:i
     */
    public function acquiaEnvironmentInfo($uuid, $environment = null)
    {

        if (!preg_match(self::UUIDv4, $uuid)) {
            $uuid = $this->getUuidFromHostingName($uuid);
        }

        if (null !== $environment) {
            $this->cloudapi->addQuery('filter', "name=${environment}");
        }

        $environments = $this->cloudapi->environments($uuid);

        $this->cloudapi->clearQuery();

        foreach ($environments as $e) {
            $this->renderEnvironmentInfo($e);
        }

        $this->say("Web servers not marked 'Active' are out of rotation.");
        $this->say("Load balancer servers not marked 'Active' are hot spares");
        $this->say("Database servers not marked 'Primary' are the passive master");
    }

    /**
     * @param $environment
     */
    protected function renderEnvironmentInfo($environment)
    {

        $environmentName = $environment->label;
        $environmentId = $environment->id;

        $this->yell("${environmentName} environment");
        $this->say("Environment ID: ${environmentId}");
        if ($environment->flags->livedev) {
            $this->say('💻  Livedev mode enabled.');
        }
        if ($environment->flags->production_mode) {
            $this->say('🔒  Production mode enabled.');
        }

        $output = $this->output();
        $table = new Table($output);
        // needs AZ?
        $table->setHeaders(array('Role(s)', 'Name', 'FQDN', 'AMI', 'Region', 'IP', 'Memcache', 'Active', 'Primary', 'EIP'));

        $servers = $this->cloudapi->servers($environment->id);

        foreach ($servers as $server) {

            $memcache = $server->flags->memcache ? '✅' : '';
            $active = $server->flags->active_web || $server->flags->active_bal ? '✅' : '';
            $primaryDb = $server->flags->primary_db ? '✅' : '';
            $eip = $server->flags->elastic_ip ? '✅' : '';

            $table
                ->addRows(array(
                    array(implode(', ', $server->roles), $server->name, $server->hostname, $server->ami_type, $server->region, $server->ip, $memcache, $active, $primaryDb, $eip),
                ));
        }

        $table->render();

    }

    /**
     * Shows SSH connection strings for specified environments.
     *
     * @param string      $uuid
     * @param string|null $environment
     *
     * @command ssh:info
     */
    public function acquiaSshInfo($uuid, $environment = null)
    {

        if (!preg_match(self::UUIDv4, $uuid)) {
            $uuid = $this->getUuidFromHostingName($uuid);
        }

        if (null !== $environment) {
            $this->cloudapi->addFilter('filter', "name=${environment}");
        }

        $environments = $this->cloudapi->environments($uuid);

        $this->cloudapi->clearQuery();

        foreach ($environments as $e) {
            $this->renderSshInfo($e);
        }
    }

    private function renderSshInfo($environment)
    {
        $environmentName = $environment->name;
        $ssh = $environment->ssh_url;
        $this->say("${environmentName}: ssh ${ssh}");
    }
}
