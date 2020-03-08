<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\BranchResponse;
use AcquiaCloudApi\Response\EnvironmentResponse;
use Symfony\Component\Console\Helper\Table;
use AcquiaCloudApi\Endpoints\Code;
use AcquiaCloudApi\Connector\Client;
use AcquiaCli\CloudApi;

/**
 * Class CodeCommand
 * @package AcquiaCli\Commands
 */
class CodeCommand extends AcquiaCommand
{

    /**
     * Gets all code branches and tags associated with an application.
     *
     * @param string $uuid
     * @param string $match A string to filter out specific code branches with.
     *
     * @command code:list
     * @aliases c:l
     */
    public function code(Client $client, Code $codeAdapter, $uuid, $match = null)
    {
        if (null !== $match) {
            $client->addQuery('filter', "name=@*${match}*");
        }
        $branches = $codeAdapter->getAll($uuid);
        $client->clearQuery();

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Tag']);

        foreach ($branches as $branch) {
            /** @var BranchResponse $branch */
            $tag = $branch->flags->tag ? '✓' : '';
            $table
                ->addRows([
                    [
                        $branch->name,
                        $tag,
                    ],
                ]);
        }

        $table->render();
    }

    /**
     * Deploys code from one environment to another.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environmentFrom
     * @param EnvironmentResponse $environmentTo
     *
     * @command code:deploy
     * @aliases c:d
     */
    public function codeDeploy(
        Code $codeAdapter,
        $uuid,
        $environmentFrom,
        $environmentTo
    ) {
        $environmentFrom = $this->cloudapiService->getEnvironment($uuid, $environmentFrom);
        $environmentTo = $this->cloudapiService->getEnvironment($uuid, $environmentTo);

        if (!$this->confirm(
            sprintf(
                'Are you sure you want to deploy code from %s to %s?',
                $environmentFrom->label,
                $environmentTo->label
            )
        )) {
            return;
        }

        $this->backupAllEnvironmentDbs($uuid, $environmentTo);

        $this->say(
            sprintf(
                'Deploying code from the %s environment to the %s environment',
                $environmentFrom->label,
                $environmentTo->label
            )
        );

        $response = $codeAdapter->deploy($environmentFrom->uuid, $environmentTo->uuid);
        $this->waitForNotification($response);
    }

    /**
     * Switches code branch on an environment.
     *
     * @param string  $uuid
     * @param string  $environment
     * @param string  $branch
     *
     * @command code:switch
     * @aliases c:s
     */
    public function codeSwitch(CloudApi $cloudapi, Code $codeAdapter, $uuid, $environment, $branch)
    {
        $environment = $cloudapi->getEnvironment($uuid, $environment);

        if (!$this->confirm(
            sprintf(
                'Are you sure you want to switch code on the %s environment to branch: %s?',
                $environment->name,
                $branch
            )
        )) {
            return;
        }

        $this->backupAllEnvironmentDbs($uuid, $environment);

        $this->say(sprintf('Switching %s enviroment to %s branch', $environment->label, $branch));

        $response = $codeAdapter->switch($environment->uuid, $branch);
        $this->waitForNotification($response);
    }
}
