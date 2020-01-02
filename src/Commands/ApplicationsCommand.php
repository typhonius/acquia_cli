<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use Symfony\Component\Console\Helper\Table;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;

/**
 * Class ApplicationsCommand
 * @package AcquiaCli\Commands
 */
class ApplicationsCommand extends AcquiaCommand
{

    /**
     * Shows all sites a user has access to.
     *
     * @command application:list
     * @alias app:list
     * @alias a:l
     */
    public function acquiaApplications()
    {
        $applicationsAdapter = new Applications($this->cloudapi);
        $applications = $applicationsAdapter->getAll();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Name', 'UUID', 'Hosting ID']);
        foreach ($applications as $application) {
            $table
                ->addRows([
                    [
                        $application->name,
                        $application->uuid,
                        $application->hosting->id,
                    ],
                ]);
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
        $environmentsAdapter = new Applications($this->cloudapi);
        $environments = $environmentsAdapter->get($uuid);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Environment', 'ID', 'Branch/Tag', 'Domain(s)', 'Database(s)']);

        foreach ($environments as $environment) {
            /** @var EnvironmentResponse $environment */

            $databases = $this->cloudapi->environmentDatabases($environment->uuid);

            $dbNames = array_map(function ($database) {
                return $database->name;
            }, $databases->getArrayCopy());

            $environmentName = $environment->label . ' (' . $environment->name . ')' ;
            if ($environment->flags->livedev) {
                $environmentName = '💻  ' . $environmentName;
            }

            if ($environment->flags->production_mode) {
                $environmentName = '🔒  ' . $environmentName;
            }

            $table
                ->addRows([
                    [
                        $environmentName,
                        $environment->uuid,
                        $environment->vcs->path,
                        implode("\n", $environment->domains),
                        implode("\n", $dbNames)
                    ],
                ]);
        }
        $table->render();
        $this->say('🔧  Git URL: ' . $environment->vcs->url);
        $this->say('💻  indicates environment in livedev mode.');
        $this->say('🔒  indicates environment in production mode.');
    }
}
