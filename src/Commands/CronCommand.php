<?php

namespace AcquiaCli\Commands;

use AcquiaCloudApi\Response\CronResponse;
use AcquiaCloudApi\Response\CronsResponse;
use AcquiaCloudApi\Response\EnvironmentResponse;
use Symfony\Component\Console\Helper\Table;

/**
 * Class CronCommand
 * @package AcquiaCli\Commands
 */
class CronCommand extends AcquiaCommand
{

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

        $crons = $this->cloudapi->crons($environment->uuid);

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
     * @command cron:add
     */
    public function cronAdd($uuid, $environment, $commandString, $frequency, $label = null)
    {
        $this->cloudapi->addCron($environment->uuid, $commandString, $frequency, $label);
        $this->waitForTask($uuid, 'CronCreated');
    }

    /**
     * Removes a cron task for an environment.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $cronId
     *
     * @command cron:delete
     */
    public function cronDelete($uuid, $environment, $cronId)
    {
        $this->cloudapi->deleteCron($environment->uuid, $cronId);
        $this->waitForTask($uuid, 'CronDeleted');
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param int                 $cronId
     *
     * @command cron:enable
     */
    public function cronEnable($uuid, $environment, $cronId)
    {
        $this->cloudapi->enableCron($environment->uuid, $cronId);
        $this->waitForTask($uuid, 'CronEnabled');
    }

    /**
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param int                 $cronId
     *
     * @command cron:disable
     */
    public function cronDisable($uuid, $environment, $cronId)
    {
        $this->cloudapi->disableCron($environment->uuid, $cronId);
        $this->waitForTask($uuid, 'CronDisabled');
    }

    /**
     * Shows detailed information about a single cron command.
     *
     * @param string              $uuid
     * @param EnvironmentResponse $environment
     * @param string              $cronId
     *
     * @command cron:info
     */
    public function cronInfo($uuid, $environment, $cronId)
    {
        $cron = $this->cloudapi->cron($environment->uuid, $cronId);

        $enabled = $cron->flags->enabled ? '✅' : '❌';
        $system = $cron->flags->system ? '✅' : '❌';
        $onAnyWeb = $cron->flags->on_any_web ? '✅' : '❌';

        $this->say('ID: ' . $cron->id);
        $this->say('Label: ' . $cron->label);
        $this->say('Environment: ' . $cron->environment->name);
        $this->say('Command: ' . $cron->command);
        $this->say('Frequency: ' . $this->convertCronFrequencyToCrontab($cron));
        $this->say('Enabled: ' . $enabled);
        $this->say('System: ' . $system);
        $this->say('On any web: ' . $onAnyWeb);

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
