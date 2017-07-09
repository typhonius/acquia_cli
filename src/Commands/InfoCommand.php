<?php

namespace AcquiaCli\Commands;

use Symfony\Component\Console\Helper\Table;

class InfoCommand extends AcquiaCommand
{

    /**
     * Gets all tasks associated with a site.
     *
     * @command task:list
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
     * Gets detailed information about a specific task
     *
     * @command task:info
     */
    public function acquiaTask($site, $taskId) {

        $tz = $this->extraConfig['timezone'];

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

    /**
     * Shows all sites a user has access to.
     *
     * @command site:list
     */
    public function acquiaSites()
    {
        $sites = $this->cloudapi->sites();
        foreach ($sites as $site) {
            $this->say($site->name());
        }
    }

    /**
     * Shows detailed information about a site.
     *
     * @command site:info
     */
    public function acquiaSiteInfo($site)
    {
        $site = $this->cloudapi->site($site);
        $environments = $this->cloudapi->environments($site);

        $this->say('Name: ' . $site->title());
        $this->say('VCS URL: ' . $site->vcsUrl());

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('Environment', 'Branch/Tag', 'Domain(s)', 'Database(s)'));

        foreach ($environments as $environment) {
            $databases = $this->cloudapi->environmentDatabases($site, $environment);
            $dbs = [];
            foreach ($databases as $database) {
                $dbs[] = $database->name();
            }
            $dbString = implode(', ', $dbs);

            $domains = $this->cloudapi->domains($site, $environment);
            $dm = [];
            foreach ($domains as $domain) {
                $dm[] = $domain->name();
            }
            $dmString = implode("\n", $dm);
            $table
                ->addRows(array(
                    array($environment->name(), $environment->vcsPath(), $dmString, $dbString),
                ));
        }
        $table->render();

    }

    /**
     * Shows detailed information about servers in an environment.
     *
     * @command environment:info
     */
    public function acquiaEnvironmentInfo($site, $environment)
    {

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('Type', 'Name', 'FQDN', 'AMI', 'Region', 'AZ', 'IP', 'Details'));


        $servers = $this->cloudapi->servers($site, $environment);
        foreach ($servers as $server) {
            $type = 'Files';
            $extra = '';

            $services = $server->services();
            if (array_key_exists('web', $services)) {
                $type = 'Web';
                $extra = 'Procs: ' . $services['web']['php_max_procs'];
                if ($services['web']['status'] != 'online') {
                    $type = '* Web';
                }
            }
            elseif (array_key_exists('vcs', $services)) {
                $type = 'Git';
            }
            elseif (array_key_exists('database', $services)) {
                $type = 'DB';
            }
            elseif (array_key_exists('varnish', $services)) {
                $type = 'LB';
                if (isset($services['external_ip'])) {
                    $extra = 'External IP: ' . $services['external_ip'];
                }
                if ($services['varnish']['status'] != 'active') {
                    $type = '* LB';
                }
            }

            $table
                ->addRows(array(
                    array($type, $server->name(), $server->fqdn(), $server->amiType(), $server->region(), $server->availabilityZone(), gethostbyname($server->fqdn()), $extra),
                ));
        }

        $table->render();

    }
}
