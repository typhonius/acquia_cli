<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\EnvironmentResponse;
use Symfony\Component\Console\Helper\Table;
use AcquiaCloudApi\Endpoints\Applications;
use AcquiaCloudApi\Endpoints\Environments;
use AcquiaCloudApi\Endpoints\Databases;
use Symfony\Component\Console\Output\OutputInterface;
use AcquiaCli\Cli\CloudApi;

/**
 * Class ApplicationsCommand
 *
 * @package AcquiaCli\Commands
 */
class ApplicationsCommand extends AcquiaCommand
{

    /**
     * Shows all sites a user has access to.
     *
     * @command application:list
     * @aliases app:list,a:l
     */
    public function applications(Applications $applicationsAdapter)
    {

        $applications = $applicationsAdapter->getAll();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Name', 'UUID', 'Hosting ID']);
        foreach ($applications as $application) {
            $table
                ->addRows(
                    [
                    [
                        $application->name,
                        $application->uuid,
                        $application->hosting->id,
                    ],
                    ]
                );
        }
        $table->render();
    }

    /**
     * Shows detailed information about a site.
     *
     * @param string $uuid
     *
     * @command application:info
     * @aliases app:info,a:i
     */
    public function applicationInfo(
        OutputInterface $output,
        Environments $environmentsAdapter,
        Databases $databasesAdapter,
        $uuid
    ) {
        $environments = $environmentsAdapter->getAll($uuid);

        $table = new Table($output);
        $table->setHeaders(['Environment', 'ID', 'Branch/Tag', 'Domain(s)', 'Database(s)']);

        $databases = $databasesAdapter->getAll($uuid);

        $dbNames = array_map(
            function ($database) {
                return $database->name;
            },
            $databases->getArrayCopy()
        );

        foreach ($environments as $environment) {
            /**
             * @var EnvironmentResponse $environment
             */

            $environmentName = sprintf('%s (%s)', $environment->label, $environment->name);
            if ($environment->flags->livedev) {
                $environmentName = sprintf('💻  %s', $environmentName);
            }

            if ($environment->flags->production_mode) {
                $environmentName = sprintf('🔒  %s', $environmentName);
            }

            $table
                ->addRows(
                    [
                    [
                        $environmentName,
                        $environment->uuid,
                        $environment->vcs->path,
                        implode("\n", $environment->domains),
                        implode("\n", $dbNames)
                    ],
                    ]
                );
        }
        $table->render();

        if (isset($environment->vcs->url)) {
            $this->say(sprintf('🔧  Git URL: %s', $environment->vcs->url));
        }
        $this->say('💻  indicates environment in livedev mode.');
        $this->say('🔒  indicates environment in production mode.');
    }

    /**
     * Shows a list of all tags on an application.
     *
     * @param string $uuid
     *
     * @command application:tags
     * @aliases app:tags
     */
    public function applicationsTags(OutputInterface $output, Applications $applicationsAdapter, $uuid)
    {
        $tags = $applicationsAdapter->getAllTags($uuid);

        $table = new Table($output);
        $table->setHeaders(['Name', 'Color']);
        foreach ($tags as $tag) {
            $table
                ->addRows(
                    [
                    [
                        $tag->name,
                        $tag->color,
                    ],
                    ]
                );
        }
        $table->render();
    }

    /**
     * Creates an application tag.
     *
     * @param string $uuid
     * @param string $name
     * @param string $color
     *
     * @command application:tag:create
     * @aliases app:tag:create
     */
    public function applicationTagCreate(Applications $applicationsAdapter, $uuid, $name, $color)
    {
        $this->say(sprintf('Creating application tag %s:%s', $name, $color));
        $response = $applicationsAdapter->createTag($uuid, $name, $color);
        $this->waitForNotification($response);
    }

    /**
     * Deletes an application tag.
     *
     * @param string $uuid
     * @param string $name
     *
     * @command application:tag:delete
     * @aliases app:tag:delete
     */
    public function applicationTagDelete(Applications $applicationsAdapter, $uuid, $name)
    {
        $this->say(sprintf('Deleting application tag %s', $name));
        $response = $applicationsAdapter->deleteTag($uuid, $name);
        $this->waitForNotification($response);
    }
}
