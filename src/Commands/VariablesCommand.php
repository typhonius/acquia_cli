<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\VariablesResponse;
use AcquiaCloudApi\Response\VariableResponse;
use AcquiaCloudApi\Endpoints\Variables;
use Symfony\Component\Console\Helper\Table;

/**
 * Class VariablesCommand
 * @package AcquiaCli\Commands
 */
class VariablesCommand extends AcquiaCommand
{

    /**
     * Lists variables.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     *
     * @command variable:list
     * @alias v:l
     */
    public function variablesList($uuid, $environment)
    {
        $variablesAdapter = new Variables($this->cloudapi);
        $variables = $variablesAdapter->getAll($environment->uuid);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['Name', 'Value']);

        foreach ($variables as $variable) {
            /** @var VariableResponse $variable */
            $table
                ->addRows([
                    [
                        $variable->name,
                        $variable->value
                    ],
                ]);
        }

        $table->render();
    }

    /**
     * Gets information about a domain.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $name
     *
     * @command variable:info
     * @aliases v:i
     */
    public function variableInfo($uuid, $environment, $name)
    {
        $variablesAdapter = new Variables($this->cloudapi);
        $variable = $variablesAdapter->get($environment->uuid, $name);
        $this->say($variable->value);
    }

    /**
     * Add a variable to an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $name
     * @param string              $value
     *
     * @command variable:create
     * @aliases variable:add,v:a
     */
    public function variableCreate($uuid, $environment, $name, $value)
    {
        $this->say(sprintf('Adding variable %s:%s to %s environment', $name, $value, $environment->label));
        $variableAdapter = new Variables($this->cloudapi);
        $response = $variableAdapter->create($environment->uuid, $name, $value);
        $this->waitForNotification($response);
    }

    /**
     * Removes an environment variable from an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $name
     * @param string              $value
     *
     * @command variable:delete
     * @aliases variable:remove,v:d,v:r
     */
    public function variableDelete($uuid, $environment, $name, $value)
    {
        if ($this->confirm('Are you sure you want to remove this environment variable?')) {
            $this->say(sprintf('Removing variable %s:%s from %s environment', $name, $value, $environment->label));
            $variableAdapter = new Variables($this->cloudapi);
            $response = $variableAdapter->delete($environment->uuid, $name, $value);
            $this->waitForNotification($response);
        }
    }

    /**
     * Updates an environment variable on an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $name
     * @param string              $value
     *
     * @command variable:update
     * @aliases v:u
     */
    public function variableUpdate($uuid, $environment, $name, $value)
    {
        if ($this->confirm('Are you sure you want to update this environment variable?')) {
            $this->say(sprintf('Updating variable %s:%s on %s environment', $name, $value, $environment->label));
            $variableAdapter = new Variables($this->cloudapi);
            $response = $variableAdapter->update($environment->uuid, $name, $value);
            $this->waitForNotification($response);
        }
    }
}
