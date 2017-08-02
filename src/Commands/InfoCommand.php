<?php

namespace AcquiaCli\Commands;

use Acquia\Cloud\Api\Response\Database;
use Acquia\Cloud\Api\Response\Domain;
use Acquia\Cloud\Api\Response\Environment;
use Acquia\Cloud\Api\Response\Server;
use Acquia\Cloud\Api\Response\Site;
use Acquia\Cloud\Api\Response\Task;
use Symfony\Component\Console\Helper\Table;

/**
 * Class InfoCommand
 * @package AcquiaCli\Commands
 */
class InfoCommand extends AcquiaCommand
{

    /**
     * Gets all tasks associated with a site.
     *
     * @param string $site
     *
     * @command task:list
     * @alias t:l
     */
    public function acquiaTasks($site)
    {

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('ID', 'User', 'State', 'Description'));

        $tasks = $this->cloudapi->tasks($site);
        /** @var Task $task */
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
     * @param string $site
     * @param string $taskId
     *
     * @command task:info
     * @alias t:i
     */
    public function acquiaTask($site, $taskId)
    {

        $tz = $this->extraConfig['timezone'];
        $format = $this->extraConfig['format'];

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
        $this->say('Created: ' . $task->created()->format($format));
        $this->say('Started: ' . $startedDate->format($format));
        $this->say('Completed: ' . $completedDate->format($format));
        $this->say('Logs: ' . $task->logs());
    }

    /**
     * Shows all sites a user has access to.
     *
     * @command site:list
     * @alias s:l
     */
    public function acquiaSites()
    {
        $sites = $this->cloudapi->sites();
        /** @var Site $site */
        foreach ($sites as $site) {
            $this->say($site->name());
        }
    }

    /**
     * Shows detailed information about a site.
     *
     * @param string $site
     *
     * @command site:info
     * @alias s:i
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

        /** @var Environment $environment */
        foreach ($environments as $environment) {
            $databases = $this->cloudapi->environmentDatabases($site, $environment);
            $dbs = [];
            /** @var Database $database */
            foreach ($databases as $database) {
                $dbs[] = $database->name();
            }
            $dbString = implode(', ', $dbs);

            $domains = $this->cloudapi->domains($site, $environment);
            $dm = [];
            /** @var Domain $domain */
            foreach ($domains as $domain) {
                $dm[] = $domain->name();
            }
            $dmString = implode("\n", $dm);

            $environmentName = $environment->name();
            if ($environment->liveDev()) {
                $environmentName = '* ' . $environmentName;
            }

            $table
                ->addRows(array(
                    array($environmentName, $environment->vcsPath(), $dmString, $dbString),
                ));
        }
        $table->render();
        $this->say('* indicates environment in livedev mode.');

    }

    /**
     * Shows detailed information about servers in an environment.
     *
     * @param string      $site
     * @param string|null $environment
     *
     * @command environment:info
     * @alias env:info
     * @alias e:i
     */
    public function acquiaEnvironmentInfo($site, $environment = null)
    {
        if (null === $environment) {
            $site = $this->cloudapi->site($site);
            $environments = $this->cloudapi->environments($site);
            /* @var $e \Acquia\Cloud\Api\Response\Environment; */
            foreach ($environments as $e) {
                $this->renderEnvironmentInfo($site, $e);
            }

            return;
        }

        $environment = $this->cloudapi->environment($site, $environment);
        $this->renderEnvironmentInfo($site, $environment);
    }

    /**
     * @param $site
     * @param Environment $environment
     */
    protected function renderEnvironmentInfo($site, Environment $environment)
    {
        $environmentName = $environment->name();

        $this->yell("${environmentName} environment");

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(array('Type', 'Name', 'FQDN', 'AMI', 'Region', 'AZ', 'IP', 'Details'));

        $servers = $this->cloudapi->servers($site, $environmentName);
        /** @var Server $server */
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
            } elseif (array_key_exists('vcs', $services)) {
                $type = 'Git';
                if (isset($services['vcs']['vcs_path'])) {
                    $extra = 'Revision: ' . $services['vcs']['vcs_path'];
                }
            } elseif (array_key_exists('database', $services)) {
                $type = 'DB';
            } elseif (array_key_exists('varnish', $services)) {
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
        $this->say('* indicates server out of rotation.');
        if ($environment->liveDev()) {
            $this->say('Livedev mode enabled.');
        }
    }

    /**
     * Shows SSH connection strings for specified environments.
     *
     * @param string      $site
     * @param string|null $environment
     *
     * @command ssh:info
     */
    public function acquiaSshInfo($site, $environment = null)
    {
        if (null === $environment) {
            $site = $this->cloudapi->site($site);
            $environments = $this->cloudapi->environments($site);
            foreach ($environments as $e) {
                $this->renderSshInfo($e);
            }

            return;
        }

        $environment = $this->cloudapi->environment($site, $environment);
        $this->renderSshInfo($environment);
    }

    private function renderSshInfo(Environment $environment)
    {
        $environmentName = $environment->name();
        $unixName = $environment['unix_username'];
        $host = $environment->sshHost();
        $this->say("${environmentName}: ssh ${unixName}@${host}");
    }
}
