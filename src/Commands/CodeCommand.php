<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\BranchResponse;
use AcquiaCloudApi\Response\EnvironmentResponse;
use Symfony\Component\Console\Helper\Table;
use AcquiaCloudApi\Endpoints\Code;

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
    public function code($uuid, $match = null)
    {
        if (null !== $match) {
            $this->cloudapi->addQuery('filter', "name=@*${match}*");
        }
        $codeAdapter = new Code($this->cloudapi);
        $branches = $codeAdapter->getAll($uuid);
        $this->cloudapi->clearQuery();

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
        $uuid,
        EnvironmentResponse $environmentFrom,
        EnvironmentResponse $environmentTo
    ) {
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

        $code = new Code($this->cloudapi);
        $response = $code->deploy($environmentFrom->uuid, $environmentTo->uuid);
        $this->waitForNotification($response);
    }

    /**
     * Switches code branch on an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $branch
     *
     * @command code:switch
     * @aliases c:s
     */
    public function codeSwitch($uuid, EnvironmentResponse $environment, $branch)
    {
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
        $code = new Code($this->cloudapi);
        $response = $code->switch($environment->uuid, $branch);
        $this->waitForNotification($response);
    }
}
