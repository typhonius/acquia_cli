<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\CronResponse;
use AcquiaCloudApi\Endpoints\Crons;
use AcquiaCloudApi\Response\EnvironmentResponse;
use Symfony\Component\Console\Helper\Table;

/**
 * Class CronCommand
 * @package AcquiaCli\Commands
 */
class CronCommand extends AcquiaCommand
{

    protected $cronAdapter;

    public function __construct()
    {
        parent::__construct();

        $this->cronAdapter = new Crons($this->getCloudApi());
    }

    /**
     * Shows all cron tasks associated with an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     *
     * @command cron:list
     */
    public function crons($uuid, $environment)
    {

        $crons = $this->cronAdapter->getAll($environment->uuid);

        $output = $this->output();
        $table = new Table($output);
        $table->setHeaders(['ID', 'Command', 'Frequency']);

        foreach ($crons as $cron) {
            $frequency = $this->convertCronFrequencyToCrontab($cron);

            $table
                ->addRows([
                    [
                        $cron->id,
                        $cron->command,
                        $frequency,
                    ],
                ]);
        }

        $table->render();
        $this->say('Cron commands starting with "#" are disabled.');
    }

    /**
     * Adds a new cron task for an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $commandString The command to be run on cron wrapped in quotes.
     * @param string              $frequency     The crontab format frequency wrapped in quotes
     * @param string              $label         An optional label for the cron command wrapped in quotes.
     *
     * @command cron:create
     * @aliases cron:add
     */
    public function cronAdd($uuid, $environment, $commandString, $frequency, $label)
    {
        $response = $this->cronAdapter->create($environment->uuid, $commandString, $frequency, $label);
        $this->waitForNotification($response);
    }

    /**
     * Removes a cron task for an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param int                 $cronId
     *
     * @command cron:delete
     * @aliases cron:remove
     */
    public function cronDelete($uuid, $environment, $cronId)
    {
        if ($this->confirm("Are you sure you want to delete the cron task?")) {
            $this->say(sprintf('Deleting cron task %s from %s', $cronId, $environment->label));
            $response = $this->cronAdapter->delete($environment->uuid, $cronId);
            $this->waitForNotification($response);
        }
    }

    /**
     * Enables a disabled cron entry.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param int                 $cronId
     *
     * @command cron:enable
     */
    public function cronEnable($uuid, $environment, $cronId)
    {
        $response = $this->cronAdapter->enable($environment->uuid, $cronId);
        $this->waitForNotification($response);
    }

    /**
     * Disables an enabled cron entry.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param int                 $cronId
     *
     * @command cron:disable
     */
    public function cronDisable($uuid, $environment, $cronId)
    {
        if ($this->confirm("Are you sure you want to disable the cron task?")) {
            $response = $this->cronAdapter->disable($environment->uuid, $cronId);
            $this->waitForNotification($response);
        }
    }

    /**
     * Shows detailed information about a single cron command.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param int                 $cronId
     *
     * @command cron:info
     */
    public function cronInfo($uuid, $environment, $cronId)
    {
        $cron = $this->cronAdapter->get($environment->uuid, $cronId);

        $enabled = $cron->flags->enabled ? '✓' : ' ';
        $system = $cron->flags->system ? '✓' : ' ';
        $onAnyWeb = $cron->flags->on_any_web ? '✓' : ' ';

        $this->say(sprintf('ID: %s', $cron->id));
        $this->say(sprintf('Label: %s', $cron->label));
        $this->say(sprintf('Environment: %s', $cron->environment->name));
        $this->say(sprintf('Command: %s', $cron->command));
        $this->say(sprintf('Frequency: %s', $this->convertCronFrequencyToCrontab($cron)));
        $this->say(sprintf('Enabled: %s', $enabled));
        $this->say(sprintf('System: %s', $system));
        $this->say(sprintf('On any web: %s', $onAnyWeb));
    }

    protected function convertCronFrequencyToCrontab(CronResponse $cron)
    {
        $frequency = [
            $cron->minute,
            $cron->hour,
            $cron->dayMonth,
            $cron->month,
            $cron->dayWeek,
        ];

        return implode(' ', $frequency);
    }
}
